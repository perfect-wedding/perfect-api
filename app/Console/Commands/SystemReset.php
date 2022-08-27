<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Madnest\Madzipper\Madzipper;
use Spatie\SlackAlerts\Facades\SlackAlert;

class SystemReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:reset
                            {action? : Action to perform [reset, backup, restore]}
                            {--w|wizard : Let the wizard help you manage system reset and restore.}
                            {--r|restore : Restore the system to the last backup or provide the --signature option to restore a known backup signature.}
                            {--s|signature= : Set the backup signature value to restore a particular known backup. E.g. 2022-04-26_16-05-34.}
                            {--b|backup : Do a complete system backup before the reset.}
                            {--d|delete : If the restore option is set, this option will delete the backup files after successfull restore.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the system (Clears database and removes related media files)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $backup = $this->option('backup');
        $restore = $this->option('restore');
        $signature = $this->option('signature');
        $delete = $this->option('delete');
        $wizard = $this->option('wizard');
        $backupDisk = Storage::disk('protected');

        if ($action === 'backup') {
            return $this->backup();
        } elseif ($action === 'restore') {
            $signatures = collect($backupDisk->allFiles('backup'))
                ->filter(fn ($f) => Str::contains($f, '.sql'))
                ->map(fn ($f) => Str::of($f)->substr(0, -4)->replace(['backup', '/-'], ''))->sortDesc()->values()->all();
            $signature = $this->choice('Backup Signature (Latest shown first):', $signatures, 0, 3);
            $delete = $this->choice('Delete Signature after restoration?', ['No', 'Yes'], 1, 2);

            return $this->restore($signature, $delete === 'Yes');
        }

        if ($wizard) {
            if (! app()->runningInConsole()) {
                $this->error('This action can only be run in a CLI.');

                return 0;
            }
            $action = $this->choice('What do you want to do?', ['backup', 'restore', 'reset'], 2, 3);

            if ($action === 'backup') {
                return $this->backup();
            } elseif ($action === 'restore') {
                $signatures = collect($backupDisk->allFiles('backup'))
                    ->filter(fn ($f) => Str::contains($f, '.sql'))
                    ->map(fn ($f) => Str::of($f)->substr(0, -4)->replace(['backup', '/-'], ''))->sortDesc()->values()->all();
                $signature = $this->choice('Backup Signature (Latest shown first):', $signatures, 0, 3);
                $delete = $this->choice('Delete Signature after restoration?', ['No', 'Yes'], 1, 2);

                return $this->restore($signature, $delete === 'Yes');
            } elseif ($action === 'reset') {
                $backup = $this->choice('Would you want do a sytem backup before reset?', ['No', 'Yes'], 1, 2);
                // Reset the system
                return $this->reset($backup === 'Yes');
            }

            return 0;
        } else {
            // Restore the system backup
            if ($restore) {
                return $this->restore($signature, $delete);
            }

            // Reset the system
            return $this->reset($backup);
        }
    }

    /**
     * Perform a backup of the system's database and uploaded media content
     *
     * @return int
     */
    protected function backup(): int
    {
        $this->info(Str::of(env('APP_URL'))->trim('/http://https://').' Is being backed up.');
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://').' Is being backed up.');

        SlackAlert::message('System backup started at: '.Carbon::now());
        $this->info('System backup started.');

        $backupDisk = Storage::disk('protected');

        // Create the backup directory if it does not exist
        if (! $backupDisk->exists('backup')) {
            $backupDisk->makeDirectory('backup');
        }
        $backupPath = $backupDisk->path('backup/');

        // Backup the database
        $filename = 'backup-'.\Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $db_backup_path = "{$backupPath}{$filename}.sql";

        $command = 'mysqldump --skip-comments'
        .' --user='.env('DB_USERNAME')
        .' --password='.env('DB_PASSWORD')
        .' --host='.env('DB_HOST')
        .' '.env('DB_DATABASE').' > '.$db_backup_path;
        $returnVar = null;
        $output = null;
        exec($command, $output, $returnVar);

        // Backup the files.
        $zip_backup_path = $backupDisk->put("backup/$filename.zip", '');
        $zip_backup_path = "{$backupPath}{$filename}.zip";
        $zip = new Madzipper;
        $zip->make($zip_backup_path)->folder('app')->add([storage_path('app')]);
        $signature = Str::of($filename)->substr(0, -4)->replace(['backup-', '/'], '');

        // Generate Link
        if (app()->runningInConsole()) {
            $link = app()->runningInConsole() ? $this->choice('Should we generate a link to download your backup files?', ['No', 'Yes'], 1, 2) : 'Yes';
            if ($link === 'Yes' && $backupDisk->exists("backup/{$filename}.sql")) {

                // Generate a downloadable link for this backup
                $zip = new Madzipper;
                $zip->make($zip_backup_path)->folder('backup')
                    ->add([$db_backup_path, $zip_backup_path]);

                $link_url = route('secure.download', $filename.'.zip');

                $mail = app()->runningInConsole() ? $this->choice('Should we mail you the link?', ['No, I\'ll copy from here.', 'Yes, mail me'], 1, 2) : 'No';

                if ($mail === 'Yes, mail me' && $zip->getFilePath()) {
                    $address = $this->ask('Email Address:');
                    Mail::send('email', [
                        'name' => ($name = collect(explode('@', $address)))->last(),
                        'message_line1' => 'You requested that we mail you a link to download your system backup.',
                        'cta' => ['link' => $link_url, 'title' => 'Download'],
                    ], function ($message) use ($address, $name) {
                        $message->to($address, $name)->subject('Backup');
                        //    $message->attach(storage_path("app/secure/$filename.zip"));
                        $message->from(env('MAIL_FROM_ADDRESS'), config('settings.site_name'));
                    });

                    SlackAlert::message("We have sent the download link to your backup file to $address.");
                    $this->info("We have sent the download link to your backup file to $address.");
                } elseif (! $zip->getFilePath()) {
                    SlackAlert::message('You have requested that we we mail you the link to your backup file but we failed to fetch the link.');
                    $this->error('Failed to fetch link.');
                } else {
                    SlackAlert::message("Download your backup file through this link: $link_url.");
                    $this->info("Download your backup file through this link: $link_url.");
                }
            } elseif (! $backupDisk->exists("backup/{$filename}.sql")) {
                $this->error('Failed to send link.');
            }
        } else {
            // Generate a downloadable link for this backup
            $zip = new Madzipper;
            $zip->make(storage_path("app/secure/$filename.zip"))->folder('backup')
                ->add([$backupPath.$filename.'.sql', $backupPath.$filename.'.zip']);

            $link_url = route('secure.download', $filename.'.zip');
            $this->info("Download your backup file through this link: $link_url.");
        }

        SlackAlert::message('System backup completed at: '.Carbon::now());
        $this->info("System backup completed successfully (Signature: $signature).");

        return 0;
    }

    /**
     * Perform a system restoration from the last or one of the available backups
     *
     * @param  string|null  $signature
     * @param  bool  $delete
     * @return int
     */
    protected function restore($signature, $delete = false): int
    {
        $this->info(Str::of(env('APP_URL'))->trim('/http://https://').' Is being restored.');
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://').' Is being restored.');

        SlackAlert::message('System restore started at: '.Carbon::now());
        $this->info('System restore started.');

        // Delete public Symbolic links
        file_exists(public_path('media')) && unlink(public_path('media'));
        file_exists(public_path('storage')) && unlink(public_path('storage'));
        file_exists(public_path('avatars')) && unlink(public_path('avatars'));
        $this->info('Public Symbolic links deleted.');

        $backupDisk = Storage::disk('protected');
        $backupPath = $backupDisk->path('backup/');

        if ($signature) {
            $database = 'backup-'.$signature.'.sql';
            $package = 'backup-'.$signature.'.zip';
        } else {
            $database = collect($backupDisk->allFiles('backup'))->filter(fn ($f) => Str::contains($f, '.sql'))->map(fn ($f) => Str::replace('backup/', '', $f))->last();
            $package = collect($backupDisk->allFiles('backup'))->filter(fn ($f) => Str::contains($f, '.zip'))->map(fn ($f) => Str::replace('backup/', '', $f))->last();
        }

        $signature = $signature ?? collect($backupDisk->allFiles('backup'))->map(fn ($f) => Str::of($f)->substr(0, -4)->replace(['backup', '/-'], ''))->last();

        $this->info(Str::of(env('APP_URL'))->trim('/http://https://').' Is being restored.');
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://').' Is being restored.');

        $canData = false;
        $canPack = false;
        if ($backupDisk->exists($path = 'backup/'.$database)) {
            $sql = $backupDisk->get($path);
            DB::unprepared($sql);
            $canData = true;
        }

        if ($backupDisk->exists($path = 'backup/'.$package)) {
            $zip = new Madzipper;
            $zip->make($backupPath.$package)->extractTo(storage_path(''));

            // Create public Symbolic links
            Artisan::call('storage:link');
            $this->info('Public Symbolic links created.');

            if ($delete) {
                unlink($backupPath.$database);
                unlink($backupPath.$package);
                $this->info("backup signature $signature deleted.");
            }
            $canPack = true;
        }

        if ($canPack || $canData) {
            $this->info("System has been restored to $signature backup signature.");

            return 0;
        }

        $this->error('System restore failed, no backup available.');

        return 1;
    }

    /**
     * Reset the system to default
     *
     * @param  bool  $backup
     * @return int
     */
    protected function reset($backup = false): int
    {
        $this->info(Str::of(env('APP_URL'))->trim('/http://https://').' Is being reset.');
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://').' Is being reset.');

        // Backup the system
        if ($backup) {
            $this->backup();
        }

        SlackAlert::message('System reset started at: '.Carbon::now());
        $this->info('System reset started.');

        // Delete public Symbolic links
        file_exists(public_path('media')) && unlink(public_path('media'));
        file_exists(public_path('storage')) && unlink(public_path('storage'));
        file_exists(public_path('avatars')) && unlink(public_path('avatars'));
        $this->info('Public Symbolic links deleted.');

        // Delete directories
        Storage::deleteDirectory('public/avatars');
        Storage::deleteDirectory('public/media');
        Storage::deleteDirectory('files/images');
        $this->info('Public directories deleted.');

        // Recreate Directories
        Storage::makeDirectory('public/avatars');
        Storage::makeDirectory('public/media');
        Storage::makeDirectory('files/images');
        $this->info('Public directories created.');

        // Create public Symbolic links
        Artisan::call('storage:link');
        $this->info('Public Symbolic links created.');

        if (Artisan::call('migrate:refresh') === 0) {
            if (Artisan::call('db:seed') === 0 && Artisan::call('db:seed HomeDataSeeder') === 0) {
                SlackAlert::message('System reset completed at: '.Carbon::now());
                $this->info('System reset completed successfully.');

                return 0;
            }
        }
        SlackAlert::message('An error occured at: '.Carbon::now().'. Unable to complete system reset.');
        $this->error('An error occured.');

        return 1;
    }
}
