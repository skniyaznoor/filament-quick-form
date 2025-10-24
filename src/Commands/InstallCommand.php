<?php

namespace FilamentQuickForm\FormBuilder\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'filament-quick-form:install';

    protected $description = 'install filament-quick-form';

    public function handle(): void
    {
        $this->info('publishing migrations...');
        $this->call('vendor:publish', ['--tag' => 'formbuilder-migrations']);

        if ($this->confirm('Do you want to run the migration now?', true)) {
            $this->info('running migrations...');
            $this->call('migrate');
        }

        $this->output->success('Quick Form has been Installed successfully, ⭐️');
    }
}