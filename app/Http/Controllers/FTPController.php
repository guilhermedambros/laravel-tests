<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FTPController extends Controller
{
    protected $FTP_SERVER = "127.0.0.1";
    protected $FTP_PATHTODONWLOAD = "example/download";
    protected $FTP_PATHTOUPLOAD = "example/upload";
    protected $FTP_USERNAME = "usuario";
    protected $FTP_USERPASSWD = "123456";
    protected $FTP_CONECTION = null;
    protected $FTP_LOGINRESULT = null;

    protected $ESTRUTURA = [
        'file1' => [
            'head' => [],
            'body' => [
                'line' => [
                    'param1' => ['position_start' => 0, 'length' => 10],
                    'param2' => ['position_start' => 10, 'length' => 5],
                    'param3' => ['position_start' => 15, 'length' => 6],
                    'param4' => ['position_start' => 21, 'length' => 10],
                ],
            ],
            'footer' => [],
        ],
    ];

    protected $CONTEUDO_UPLOAD = [
        ["param1" => "linha11111", "param2" => "22222", "param3" => "333333", "param4" => "4444444444"],
        ["param1" => "linha22222", "param2" => "22222", "param3" => "333333", "param4" => "4444444444"],
        ["param1" => "linha3", "param2" => "22222", "param3" => "333333", "param4" => "4444444444"],
        ["param1" => "linha444444444", "param2" => "22222", "param3" => "", "param4" => "4444444444"],
        ["param1" => "linha5", "param2" => "22222", "param3" => "333333", "param4" => "4444444444"],
    ];

    public function __construct(){
        try{
            $this->FTP_CONECTION = ftp_connect($this->FTP_SERVER);
            // login with username and password
            $this->FTP_LOGINRESULT = ftp_login($this->FTP_CONECTION, $this->FTP_USERNAME, $this->FTP_USERPASSWD);
        }catch(\Exception $e){
            dd($e->getMessage());
        }
        
    }

    public function read(){    
        // get contents of the current directory
        $contents = ftp_nlist($this->FTP_CONECTION, $this->FTP_PATHTODONWLOAD);
        // mostra o diretório atual
        echo ftp_pwd($this->FTP_CONECTION); // 
        // fecha esta conexão
        $files_to_import = $this->get_files_to_import($contents);
        $this->import($files_to_import);
        ftp_close($this->FTP_CONECTION);
    }

    private function get_files_to_import($file_list){
        $files_to_import = [];
        foreach($file_list as $file_name){
            if(true){
                $files_to_import[] = $file_name;
            }
        }
        return $files_to_import;

    }


    private function import($file_list){
        foreach($file_list as $file_name){
            ob_start();
            $result = ftp_get($this->FTP_CONECTION, "php://output", $this->FTP_PATHTODONWLOAD.'/'.$file_name, FTP_BINARY);
            $data = ob_get_contents();
            ob_end_clean();
            $this->import_lines($data);
            /*if (ftp_get($conn_id, $local_file, $server_file, FTP_BINARY)) {
                echo "Successfully written to $local_file\n";
            } else {
                echo "There was a problem\n";
            }*/
        }

    }

    private function import_lines($file_content){
        $lines = explode("\n", $file_content);
        $items = [];
        foreach($lines as $key => $line){
            $items[] = $this->get_params_by_position($line, 'file1');
        }
        dd($items);
    }

    private function get_params_by_position($line, $defaultFile){
        $params = $this->ESTRUTURA[$defaultFile]['body']['line'];
        $item = [];
        foreach($params as $index => $value){
            $item[$index] = substr($line, $value['position_start'], $value['length']);
        }
        return $item;
    }

    public function write (){    
        $content = $this->prepare_content('file1');
        $this->write_and_upload_file($content, 'FILETEST'.date('YmdGis'));
        ftp_close($this->FTP_CONECTION);
    }

    private function prepare_content($defaultFile){

        $params = $this->ESTRUTURA[$defaultFile]['body']['line'];
        $content_to_write = $this->CONTEUDO_UPLOAD;
        $lines = [];
        foreach($content_to_write as $key => $arr_values){
            
            $line = '';
            foreach($arr_values as $index => $value){
                $line .=  substr(str_pad("$value", $params[$index]['length'], " ", STR_PAD_RIGHT ), 0, $params[$index]['length']);
                //substr($line, $value['position_start'], $value['length']);
            }
            $lines[] = $line;
        }
        return $lines;
    }

    private function write_and_upload_file($content, $file_name){
        $file = fopen("$file_name.txt", "w+") or die("Unable to open file!");
        foreach($content as $key => $line){
            fwrite($file, $line."\n");
        }     

        fclose($file);
        
        $this->upload($file, "$file_name.txt");
    }

    private function upload($file, $file_name){ 
        try{
            $file = fopen("$file_name", "r+") or die("Unable to open file!");
            ftp_fput($this->FTP_CONECTION, "$this->FTP_PATHTOUPLOAD/$file_name", $file, FTP_ASCII);
            fclose($file);
            unlink($file_name);
        } catch(\Exception $e){
            ftp_close($this->FTP_CONECTION);
            dd($e->getMessage());
        }
        
    }
}
