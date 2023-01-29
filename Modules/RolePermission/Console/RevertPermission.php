<?php

namespace Modules\RolePermission\Console;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RevertPermission extends Command
{
  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = 'revert:permissions';


  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command for revert permissions.';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  public function handle()
  {
    $this->info("*Reverting Permission**");
    $default_roles = config('rolepermission.roles');
    foreach ($default_roles as $role_id => $name) {
      $role = Role::where('name', '=', $name)->first();
      if (!$role) {
        Role::create([
          'name' => $name
        ]);
        $this->info("Role " . $name . " created.");
      }
    }

    $modules = Module::allEnabled();
    $all_permissions = [];
    foreach ($modules as $module) {
      $config_path = $module->getLowerName();
      $configs = config($config_path,[]);
      $has_permissions = array_key_exists('permissions', $configs);
      if ($has_permissions) {
        $permissions = $configs['permissions'];
        foreach ($permissions as $permission_key => $permission) {
          Permission::firstOrCreate([
            'name' => $permission_key,
            'module' => $configs['name']
          ]);
          $all_permissions[$permission_key] = $permission_key;
        }
        $this->info("All permissions synced for " . $module->getName());
      } else {
        $this->info("No permissions defined for " . $config_path);
      }
    }
    // delete removed permissions
    $permissions_db = Permission::all();
    if (count($permissions_db) != count($all_permissions)) {
      foreach ($permissions_db as $permission) {
        if (!in_array($permission->name, $all_permissions)) {
          $this->info("Permission " . $permission->name . " deleted.");
          $permission->delete();
        }
      }
    }

    // Assigning all permissions to admin
    $admin = Role::findByName('Admin');
    $admin->syncPermissions(Permission::all());
    $this->info("*Reverting Permission Completed**");
  }

  protected function getArguments()
  {
    return [
    ];
  }

  /**
   * Get the console command options.
   *
   * @return array
   */
  protected function getOptions()
  {
    return [
    ];
  }
}
