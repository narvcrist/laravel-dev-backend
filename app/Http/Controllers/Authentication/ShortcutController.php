<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\Authentication\Permission;
use App\Models\Authentication\Shortcut;
use App\Models\Authentication\User;
use App\Models\Authentication\Role;
use App\Models\Ignug\State;
use Illuminate\Http\Request;

class ShortcutController extends Controller
{
    public function index(Request $request)
    {
        $shortcuts = Permission::whereHas('shortcut', function ($shortcut) use ($request) {
            $shortcut
                ->where('role_id', $request->role_id)
                ->where('user_id', $request->user_id);
        })->with('shortcut')
            ->with('route')
            ->where('institution_id', $request->institution_id)
            ->get();

        $shortcuts = Shortcut::
        with(['permission' => function ($permission) use ($request) {
            $permission->with('route')->where('institution_id', $request->institution_id);
        }])
            ->where('role_id', $request->role_id)
            ->where('user_id', $request->user_id)
            ->get();
        return response()->json([
            'data' => $shortcuts,
            'msg' => [
                'summary' => 'success',
                'detail' => '',
                'code' => '200'
            ]], 200);
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();
        $dataShortcut = $data['shortcut'];

        $shortcut = new Shortcut();
        $shortcut->user()->associate(User::findOrFail($request->user_id));
        $shortcut->role()->associate(Role::findOrFail($request->role_id));
        $shortcut->permission()->associate(Permission::findOrFail($dataShortcut['permission_id']));
        $shortcut->image = $dataShortcut['image'];
        $shortcut->save();

        return response()->json([
            'data' => $shortcut,
            'msg' => [
                'summary' => 'success',
                'detail' => '',
                'code' => '201'
            ]], 201);
    }

    public function destroy($id)
    {
        $shortcut = Shortcut::findOrFail($id);
        $shortcut->delete();
        return response()->json([
            'data' => $shortcut,
            'msg' => [
                'summary' => 'success',
                'detail' => '',
                'code' => '201'
            ]], 201);
    }

}
