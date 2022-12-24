<?php

namespace App\Console\Commands;

use App\Models\v1\Company;
use App\Models\v1\Inventory;
use App\Models\v1\Service;
use App\Models\v1\User;
use Illuminate\Console\Command;

class Fixer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:fix
                            {method? : method to run}
                            {--all : run all methods}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix some issues in the system';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $method = $this->argument('method');
        $all = $this->option('all');

        if ($all) {
            $this->fixAll();
        } elseif ($method) {
            $this->fix($method);
        } else {
            $this->error('Please specify a method to run or use --all to run all methods');
        }

        return Command::SUCCESS;
    }

    /**
     * Run all methods
     *
     * @return void
     */
    protected function fixAll()
    {
        $methods = collect(get_class_methods($this))
                    ->filter(fn ($method) => str($method)->startsWith('fix') && ! in_array($method, ['fixAll', 'fix']))
                    ->toArray();

        foreach ($methods as $method) {
            $this->fix($method);
        }
    }

    /**
     * Run a method
     *
     * @param  string  $method
     * @return void
     */
    protected function fix(string $method)
    {
        if (method_exists($this, $method)) {
            $this->info("Running $method");
            $this->$method();
        } else {
            $this->error("Method $method does not exist");
        }
    }

    /**
     * Fix the issue with services
     *
     * @return void
     */
    protected function fixServices()
    {
        $services = Service::where('slug', null)->cursor();
        $this->info("Found {$services->count()} services with null slug");
        $services->each(function ($service) {
            $service->slug = str($service->name)->slug();
            $service->save();
        });

        // Find services with duplicate slugs
        $services = Service::select('slug')->groupBy('slug')->havingRaw('count(*) > 1')->cursor();
        $this->info("Found {$services->count()} services with duplicate slugs");
        $services->each(function ($service) {
            $services = Service::where('slug', $service->slug)->cursor();
            $services->each(function ($service, $index) {
                $service->slug = str($service->slug)->slug().'-'.($index + 1);
                $service->save();
            });
        });

        // Find services with duplicate titles
        $services = Service::select('title')->groupBy('title')->havingRaw('count(*) > 1')->cursor();
        $this->info("Found {$services->count()} services with duplicate titles");
        $services->each(function ($service) {
            $services = Service::where('title', $service->title)->cursor();
            $services->each(function ($service, $index) {
                $service->title = str($service->title)->slug().'-'.($index + 1);
                $service->save();
            });
        });

        // Find services where title is all lowercase characters
        $services = Service::whereRaw('LOWER(title) = title')->cursor();
        $this->info("Found {$services->count()} services with lowercase titles");
        $services->each(function ($service) {
            $service->title = str($service->title)->title();
            $service->save();
        });

        $this->info('Done fixing services');
    }

    /**
     * Fix the issue with inventory
     *
     * @return void
     */
    protected function fixInventory()
    {
        $inventory = Inventory::where('slug', null)->cursor();
        $this->info("Found {$inventory->count()} inventory items with null slug");
        $inventory->each(function ($item) {
            $item->slug = str($item->name)->slug();
            $item->save();
        });

        // Find inventory items with duplicate slugs
        $inventory = Inventory::select('slug')->groupBy('slug')->havingRaw('count(*) > 1')->cursor();
        $this->info("Found {$inventory->count()} inventory items with duplicate slugs");
        $inventory->each(function ($item) {
            $items = Inventory::where('slug', $item->slug)->cursor();
            $items->each(function ($item, $index) {
                $item->slug = str($item->slug)->slug().'-'.($index + 1);
                $item->save();
            });
        });

        // Find inventory items with duplicate names
        $inventory = Inventory::select('name')->groupBy('name')->havingRaw('count(*) > 1')->cursor();
        $this->info("Found {$inventory->count()} inventory items with duplicate names");
        $inventory->each(function ($item) {
            $items = Inventory::where('name', $item->name)->cursor();
            $items->each(function ($item, $index) {
                $item->name = str($item->name)->slug().'-'.($index + 1);
                $item->save();
            });
        });

        // Find inventory items where name is all lowercase characters
        $inventory = Inventory::whereRaw('LOWER(name) = name')->cursor();
        $this->info("Found {$inventory->count()} inventory items with lowercase names");
        $inventory->each(function ($item) {
            $item->name = str($item->name)->title();
            $item->save();
        });

        $this->info('Done fixing inventory');
    }

    /**
     * Fix the issue with company
     *
     * @return void
     */
    protected function fixCompany()
    {
        $company = Company::where('slug', null)->cursor();
        $this->info("Found {$company->count()} companies with null slug");
        $company->each(function ($company) {
            $company->slug = str($company->name)->slug();
            $company->save();
        });

        // Find companies with duplicate slugs
        $company = Company::select('slug')->groupBy('slug')->havingRaw('count(*) > 1')->cursor();
        $this->info("Found {$company->count()} companies with duplicate slugs");
        $company->each(function ($company) {
            $companies = Company::where('slug', $company->slug)->cursor();
            $companies->each(function ($company, $index) {
                $company->slug = str($company->slug)->slug().'-'.($index + 1);
                $company->save();
            });
        });

        // Find companies with duplicate names
        $company = Company::select('name')->groupBy('name')->havingRaw('count(*) > 1')->cursor();
        $this->info("Found {$company->count()} companies with duplicate names");
        $company->each(function ($company) {
            $companies = Company::where('name', $company->name)->cursor();
            $companies->each(function ($company, $index) {
                $company->name = str($company->name)->slug().'-'.($index + 1);
                $company->save();
            });
        });

        // Find companies where name is all lowercase characters
        $company = Company::whereRaw('LOWER(name) = name')->cursor();
        $this->info("Found {$company->count()} companies with lowercase names");
        $company->each(function ($company) {
            $company->name = str($company->name)->title();
            $company->save();
        });

        // Delete unverified companies with no services or inventory
        $company = Company::whereDoesntHave('services')->whereDoesntHave('inventories')->verified(false)->cursor();
        $this->info("Found {$company->count()} unverified companies with no services or inventory");
        $company->each(function ($company) {
            $company->delete();
        });

        $this->info('Done fixing companies');
    }

    /**
     * Fix the issue with users
     *
     * @return void
     */
    protected function fixUsers()
    {
        // Delete users with no company, services, inventory, transactions, orderRequests, albums, board, wallet_transactions, tasks, subscriptions, made or received reviews, clients, or messages
        $users = User::whereDoesntHave('company')
                ->whereDoesntHave('companies')
                ->whereDoesntHave('transactions')
                ->whereDoesntHave('orders')
                ->whereDoesntHave('orderRequests')
                ->whereDoesntHave('albums')
                ->whereDoesntHave('boards')
                ->whereDoesntHave('wallet_transactions')
                ->whereDoesntHave('tasks')
                ->whereDoesntHave('subscription')
                ->whereDoesntHave('reviewsBy')
                ->whereDoesntHave('clients')
                ->whereDoesntHave('reviews')
                ->whereDoesntHave('messages')
                ->cursor();
        $this->info("Found {$users->count()} users with no company, services, inventory, transactions, orderRequests, albums, board, wallet_transactions, tasks, subscriptions, made or received reviews, clients, or messages");
        $users->each(function ($user) {
            $user->delete();
        });

        $this->info('Done fixing users');
    }
}
