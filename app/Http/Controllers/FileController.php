<?php
namespace App\Http\Controllers;

use App\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function uploadImage(Request $request)
    {
        if (!$request->hasFile('file') || !$request->file('file')->isValid() || !$request->has('roomid')) {
            return response()->json([
                'data' => [
                    'errno' => 500,
                    'msg'   => '无效的参数（房间号/图片文件为空或者无效）'
                ]
            ]);
        }
        $image = $request->file('file');
        $time = time();
        $filename = md5($time . mt_rand(0, 10000)) . '.' . $image->extension();
        $path = $image->storeAs('images/' . date('Y/m/d', $time), $filename, ['disk' => 'public']);
        if ($path) {
            $message = new Message();
            $message->user_id = auth('api')->id();
            $message->room_id = $request->post('roomid');
            $message->msg = '';  // 文本消息
            $message->img = Storage::disk('public')->url($path);
            $message->created_at = Carbon::now();
            $message->save();
            return response()->json([
                'data' => [
                    'errno' => 200,
                    'msg'   => '保存成功'
                ]
            ]);
        } else {
            return response()->json([
                'data' => [
                    'errno' => 500,
                    'msg'   => '文件上传失败，请重试'
                ]
            ]);
        }
    }
}
