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
    /**
     * $request - Request Object
     * $fileKey - file form name
     *  > ex: <input type="file" name="fileObject" /> -> "fileObject"
     * $type - 유효성 검사 타입(image, video, file)
     *  > image : 이미지 파일만
     *  > video : 비디오 파일만
     *  > file : 모든 파일
     * $path - 업로드 경로(기본값 : 'temp/' ) * 끝에 반드시 '/'부호를 붙여주어야 함
     */
    public function fileUpload(Request $request, $fileKey = 'file', $type = '', $path = 'temp/') : array
    {
        $result = array();
        /**
         * 유효성 검사
         */
        $conditions = '';
        if( $type == 'image' ) {
            $conditions = 'required|image|max:2048';
        } else if( $type == 'video' ) {
            $conditions = 'required|mimetypes:video/x-ms-asf,video/x-flv,video/mp4,application/x-mpegURL,video/MP2T,video/3gpp,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/avi|max:20480';
        } else {
            $conditions = 'required|file|max:10240';
        }
        $validator = Validator::make($request->all(), [
            $fileKey => $conditions,
        ]);

        if($validator->fails()) {
            $result['result'] = false;
            $result['messages'] = $validator->messages();
            $result['status_code'] = 400;
            return $result;
        }

        $fileData = $request->file;        
        $logical_name = $request->file( $fileKey )->getClientOriginalName();

        $extension = $request->file( $fileKey )->extension();
        $physical_name = round(microtime(true)) . '.' .$extension; 
        $path = $path == '' ? 'temp/' : $path;

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
            $fileInfo['size'] = $request->file( $fileKey )->getSize();
            $fileInfo['mimetype'] = $request->file( $fileKey )->getMimeType();
            // DB(tb_file) insert
            $file_seq = DB::table('tb_file')->insertGetId( $fileInfo, 'file_seq');

            $result['result'] = true;
            $fileInfo['file_seq'] = $file_seq;
            $result['data'] = $fileInfo;

            return $result;
        }catch(Exception $e){
            report($e);

            $result['result'] = false;
            $result['messages'] = 'S3 or DB insert error';
            $result['status_code'] = 500;

            return $result;
        }
    }

}