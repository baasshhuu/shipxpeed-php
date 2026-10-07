<?php

namespace App\Http\Controllers\Common;

use App\Models\User;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class CommonController extends Controller
{
    public function upload_image(Request $request): string
    {
        if ($request->file('image')) {
            $image = Helper::saveFile($request->file('image'), 'images');
            return asset('storage/' . $image);
        }
    }

    public function get_user_list_filter(Request $request): JsonResponse
    {
        $query = User::select("id, CONCAT(name,' (', mobile,')') as name");
        $query->active();
        $query->limit(50);
        if ($request->has('filter')) {
            $query->where('name', 'like', '%' . $request->get('filter') . '%');
            $query->orWhere('mobile', 'like', '%' . $request->get('filter') . '%');
        }

        $user =   $query->get();
        return response()->json($user);
    }

    public function test(Request $request)
    {
        abort(404);
        echo "Records updated Successfully..!!";
    }
}
