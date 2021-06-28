<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Aws\S3\S3Client;
use Exception;

class UtilController extends Controller
{
    function fileUpload(Request $request){
        $result = array();

        /**
         * 이미지 파일만 업로드
         * todo : 비디오/이미지 = 미디어
         * todo : 문서
         * todo : 다른 클래스에서 호출될때, 파일경로, 파라메터명, 파일타입등을 받아 유효성 검사 및 후처리 가능하도록 수정
         */
        $validator = Validator::make($request->all(), [
            'file' => 'required|image',
        ]);

        /**
         * 유효성검사 실패 시, 
         */
        if($validator->fails()) {
            return response()->json([
                'result' => false,
                'messages' => $validator->messages()
            ], 401);
        }

        $fileData = $request->file;        
        $logical_name = $request->file('file')->getClientOriginalName();

        // $ext = preg_replace('/^.*\.([^.]+)$/D', '$1', $logical_name);
        $ext = $request->file('file')->extension();
        $physical_name = round(microtime(true)).".".$ext;
        $path = "temp/";

        $sharedConfig = [
            'region' => 'ap-northeast-2',
            'scheme' => 'http',
            'version' => 'latest',
        ];

        try {
            $s3Client = new S3Client($sharedConfig);
            // S3 Upload
            $s3result = $s3Client->putObject([
                'Bucket' => "static.chosajang.com",
                'Key' => $path.$physical_name,
                'SourceFile' => $fileData,
            ]);

            $fileInfo = array();
            $fileInfo['physical_name'] = $physical_name;
            $fileInfo['logical_name'] = $logical_name;
            $fileInfo['path'] = $path;
            $fileInfo['size'] = $request->file('file')->getSize();
            $fileInfo['mimetype'] = $request->file('file')->getMimeType();
            // DB(tb_file) insert
            $file_seq = DB::table('tb_file')->insertGetId( $fileInfo, 'file_seq');

            $result['result'] = true;
            $fileInfo['file_seq'] = $file_seq;
            $result['data'] = $fileInfo;

            return response()->json($result, 201);
        }catch(Exception $e){
            report($e);

            $result['result'] = false;
            $result['messages'] = 'S3 or DB insert error';

            return response()->json($result, 500);
        }
    }
}