<?php

namespace Modules\ChatMini\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SetupChatmini extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'setup:system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup system.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }
    public function handle() {
        $this->info("Setting up system...");
        Artisan::call("revert:permissions");
        $this->info("Roles and Permissions Reverted");
    }
    protected function getArguments(){
        return [
        ];
    }
    protected function getOptions(){
        return [
        ];
    }
}
