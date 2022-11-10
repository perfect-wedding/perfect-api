# Perfect Wedding Backend

PerfectWedding.io is a tech company with the goal to use technology to provide marketplace vendors/service providers a seamless way to connect to the massive customer needs in this industry.

## Install the dependencies

```bash
# or
composer update
```

### Running Queues

Laravel includes an Artisan command that will start a queue worker and process new jobs as they are pushed onto the queue. You may run the worker using the `queue:work` Artisan command. Note that once the `queue:work` command has started, it will continue to run until it is manually stopped or you close your terminal [Laravel Docs](https://laravel.com/docs/9.x/queues#running-the-queue-worker)

The system dispatches tasks and jobs requiring heavy system resource consumtion to a queue thereby limiting the strain on the user waiting for these tasks to complete. You may run the following command to begin processing all queues:

```bash
php artisan queue:work
```

To keep the `queue:work` process running permanently in the background, you should use a process monitor such as [Supervisor](https://laravel.com/docs/9.x/queues#supervisor-configuration) to ensure that the queue worker does not stop running.

### Running The Scheduler

Where it is not possible to run queues, the task scheduller has also been implemented as an alternative that will automaticaally proccess queues and other schedulled tasks:

```bash
* * * * * cd /installation-path && php artisan schedule:run >> /dev/null 2>&1
```

This is best run as a cron job.
