<?php

namespace Modules\UserSystem\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Modules\UserSystem\Entities\User;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UserSystem extends Command {
    protected $name = 'chatmini:user';
    protected $description = 'Command description.';
    protected $signature = 'chatmini:user {op} {--user=1} {--roles=} {--presence=} {--append} {--revoke}';
    public function __construct() {
        parent::__construct();
    }
    public function handle() {
        $datas = [
          'op' => $this->argument('op'),
          'id' => $this->option('user'),
          'presence' => $this->option('presence'),
          'roles' => $this->option('roles'),
          'append' => $this->option('append'),
          'revoke' => $this->option('revoke'),
        ];
        # Validate user
        $user = User::with(['roles'])->find($datas['id']);
        if($datas['op'] == 'update') {
          if(!empty($datas['presence'])) {
            if(!in_array($datas['presence'],['online','offline','busy','away'])) {
              throw new \Exception("Presence not found.");
            }
            $user->pres = $datas['presence'];
            $this->info("Presence updated. :)");
          } // Changed presence
          if(!empty($datas['roles'])) {
            $roles = explode(",", $datas['roles']);
            if(!empty($datas['append'])) {
             $old_roles = $user->roles->pluck("name")->toArray();
             $roles = array_unique(array_merge($roles, $old_roles));
            }
            if(!empty($datas['revoke'])) {
             $old_roles = $user->roles->pluck("name")->toArray();
             $roles = array_diff(array_unique(array_merge($roles, $old_roles)), $roles);
            }
            $user->syncRoles($roles);
            $this->info("Roles updated :)");
          }
          $user->save();
          $this->info("User is updated. :)");
        }
    }
    protected function getArguments()
    {
        return [

        ];
    }
    protected function getOptions()
    {
        return [
        ];
    }
}
