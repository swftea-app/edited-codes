<?php

namespace Modules\RolePermission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller {
    public function __construct() {
      $this->middleware(['auth','can:administer rolepermission']);
    }
    public function index() {
      $headings = ["Permission"];
      $body = [];
      $selected_permission_all = [];
      $roles = Role::with('permissions')->get();
      $permissions = Permission::all();
      foreach ($roles as $role) {
        $headings[] = $role->name;
        $selected_permissions = $role->permissions->pluck('name');
        $selected_permission_all[$role->name] = [];
        foreach ($selected_permissions as $selected_permission) {
          $selected_permission_all[$role->name][] = $selected_permission;
        }
      }
      foreach ($permissions as $permission) {
        $body[$permission->module][] = (object)[
          'name' => $permission->name,
          'id' => $permission->id
        ];
      }

      return view('rolepermission::index')->with([
        'headings' => $headings,
        'bodies' => $body,
        'selected_permissions' => $selected_permission_all
      ]);
    }
    public function store(Request $request) {
        $permissions = $request->permissions;
        $all_roles = config('rolepermission.roles');
        foreach ($all_roles as $name) {
          $role = Role::findByName($name);
          $role->permissions()->detach();
        }
        foreach ($permissions as $role_name => $permission) {
          $role = Role::findByName($role_name);
          $all_permissions = array_keys($permission);
          $role->syncPermissions($all_permissions);
        }
        Alert::success("Success","Roles and Permissions updated.");
        return back();
    }
}
