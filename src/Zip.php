<?php
namespace hulang;
// +----------------------------------------------------------------------
// | 小黄牛MVC框架 - Vendor - Zip - 内置目录、单文件打包压缩解压类
// +----------------------------------------------------------------------
// | 支持全部后缀类型压缩解压，可选是否输出压缩下载
// | SaveZip('./','./');        目录打包
// | UnZip('./asd.rar','./1/'); 压缩包解压
// +----------------------------------------------------------------------
// | Copyright (c) 2016 www.junphp.com
// +----------------------------------------------------------------------
// | Author: 冯俊豪 - 小黄牛 <1731223728@qq.com>
// +----------------------------------------------------------------------
class Zip
{
    //目录深度
    private $file_count = 0;
    //压缩包大小
    private $datastr_len = 0;
    //目录大小
    private $dirstr_len = 0;
    //压缩包文件名
    private $gzfilename = '';
    //压缩包格式
    private $ziptype = '';
    //压缩包写入权限
    private $fp;
    //目录信息
    private $dirstr = '';
    //文件目录信息
    private $Array = '';
    private $filefilters = array();
    /**
     * 创建压缩文件
     * $SavePath : 压缩包保存到的路径
     * $Pack     ：需要打包的目录或文件 
     * $SaveName ：压缩包保存的文件名       默认为空,为空时则使用时间戳充当为文件名,禁止使用中文
     * $ZipType  : 压缩包保存的类型        默认为zip
     * $Output   ：压缩完成是否输出下载     默认为FALSE   设置为TRUE则输出下载
     * @成功返回压缩包路径，错误返回false
     */
    public function SaveZip($SavePath, $Pakc, $SaveName = '', $ZipType = 'zip', $Output = false)
    {
        //1、判断压缩包保存是否自定义文件名
        if (empty($SaveName)) {
            //使用时间戳当做压缩包保存的文件名
            $ZipSaveName = time();
            //拼接保存路径
            $Path = $SavePath . $ZipSaveName . '.' . $ZipType;
        } else {
            $ZipSaveName = $SaveName;
            //拼接保存路径
            $Path = $SavePath . $ZipSaveName . '.' . $ZipType;
            if (is_file($Path)) {
                die($Path . ' -> 文件名已存在！');
            }
        }
        //将保存路径赋值给成员
        $this->gzfilename = $Path;
        //2、判断打包路径中是否带有文件名
        //先删除左侧.号
        $Path = ltrim($Pakc, '.');
        //找不到.号则为目录打包
        if (strpos($Path, '.') === false) {
            //3、判断打包目录是否正确
            if (!is_dir($Pakc)) {
                die($Pakc . ' -> 需要打包的目录不存在！');
            } else {
                //4、先生成空的压缩包,返回写入权限
                $SetFile = $this->SetFile();
                if ($SetFile === false) {
                    die('压缩包生成失败,-1！');
                }
                //将路径分割
                $this->ListFile($Pakc);
                $Path = explode('|', rtrim($this->Array, '|'));
                //删除压缩包的索引
                unset($Path[array_search($this->gzfilename, $Path)]);
                foreach ($Path as $value) {
                    //向压缩包里加入内容
                    $res = $this->AddFile($value);
                    if ($res === false) {
                        die('向压缩包内添加：' . $value . '发生错误！');
                    }
                }
            }
        } else {
            //3、判断压缩文件是否存在
            if (!is_file($Pakc)) {
                die($Pakc . ' -> 需要压缩的文件名不存在！');
            } else {
                //4、先生成空的压缩包,返回写入权限
                $SetFile = $this->SetFile();
                if ($SetFile === false) {
                    die('压缩包生成失败,-1！');
                }
                $res = $this->AddFile($Pakc);
                if ($res === false) {
                    die('压缩包生成失败,-2！');
                }
            }
        }
        //5、判断是否要求输出下载
        if ($Output === true) {
            $this->ZipDow($this->gzfilename);
        }
        //释放资源
        $this->createfile();
    }
    /**
     * 解压文件到指定目录
     * $src_file   string   zip压缩文件的路径	
     * $dest_dir   string   解压文件的目的路径	
     * $overwrite  boolean  是否重写已经存在的文件
     * $type       boolean  是否需要删除压缩包	
     * @return     boolean  返回成功 或失败	
     */
    public function UnZip($src_file, $dest_dir = '', $overwrite = true, $type = false)
    {
        if ($zip = zip_open($src_file)) {
            if ($zip) {
                $splitter = '/';
                // 如果不存在 创建目标解压目录
                $this->create_dirs($dest_dir);
                // 对每个文件进行解压
                // 文件不在根目录
                while ($zip_entry = zip_read($zip)) {
                    $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                    // 创建目录 在末尾带 /
                    if ($pos_last_slash !== false) {
                        $this->create_dirs($dest_dir . substr(zip_entry_name($zip_entry), 0, $pos_last_slash + 1));
                    }
                    // 打开包
                    // 文件名保存在磁盘上
                    if (zip_entry_open($zip, $zip_entry, "r")) {
                        $file_name = $dest_dir . zip_entry_name($zip_entry);
                        // 检查文件是否需要重写
                        if ($overwrite === true || $overwrite === false && !is_file($file_name)) {
                            // 读取压缩文件的内容
                            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                            @file_put_contents($file_name, $fstream);
                            // 设置权限
                            chmod($file_name, 0666);
                        }
                        // 关闭入口
                        zip_entry_close($zip_entry);
                    }
                }
                // 关闭压缩包
                zip_close($zip);
                if ($type === true) {
                    if (unlink($src_file)) {
                        return true;
                    } else {
                        die('删除压缩包失败！');
                    }
                }
            }
        } else {
            return false;
        }
        return true;
    }
    /**
     * 初始化文件,建立文件目录,以及生产空压缩包，只对文件压缩时使用
     * @返回文件的写入权限
     */
    private function SetFile()
    {
        //获得压缩包名称
        $path = basename($this->gzfilename);
        //返回去掉文件名后的目录名
        $pathurl = dirname($this->gzfilename);
        $pathurl = explode('/', $pathurl);
        //获得路径中的第一个元素，并且从数组中删除
        $str = array_shift($pathurl);
        $url = '';
        foreach ($pathurl as $value) {
            $url .= '/' . $value;
            //判断目录是否存在
            if (!is_dir($str . $url)) {
                //创建目录
                mkdir($str . $url);
            }
        }
        //创建压缩包，并且返回写入权限
        if ($this->fp = fopen($this->gzfilename, "w")) {
            return true;
        }
        return false;
    }
    /**	
     * 创建目录,只对文件解压时使用
     */
    private function create_dirs($path)
    {
        if (!is_dir($path)) {
            $directory_path = "";
            $directories = explode("/", $path);
            array_pop($directories);
            foreach ($directories as $directory) {
                $directory_path .= $directory . "/";
                if (!is_dir($directory_path)) {
                    mkdir($directory_path);
                    chmod($directory_path, 0666);
                }
            }
        }
    }
    /**
     * 向压缩包内添加一个文件
     * $name : 文件路径
     */
    private function AddFile($name)
    {
        //读取文件内容
        if (file_exists($name)) {
            $fp = fopen($name, "r");
            $data = '';
            //每次读取 1024 字节
            $buffer = 1024;
            //循环读取，直至读取完整个文件
            while (!feof($fp)) {
                $data .= fread($fp, $buffer);
            }
        } else {
            return false;
        }
        $dtime = dechex($this->unix2DosTime());
        $hexdtime = '\\x' . $dtime[6] . $dtime[7] . '\\x' . $dtime[4] . $dtime[5] . '\\x' . $dtime[2] . $dtime[3] . '\\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');
        $unc_len = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $c_len = strlen($zdata);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
        //新添文件内容格式化:
        $datastr = "PK\3\4";
        $datastr .= "\24\0";
        $datastr .= "\0\0";
        $datastr .= "\10\0";
        $datastr .= $hexdtime;
        $datastr .= pack('V', $crc);
        $datastr .= pack('V', $c_len);
        $datastr .= pack('V', $unc_len);
        $datastr .= pack('v', strlen($name));
        $datastr .= pack('v', 0);
        $datastr .= $name;
        $datastr .= $zdata;
        $datastr .= pack('V', $crc);
        $datastr .= pack('V', $c_len);
        $datastr .= pack('V', $unc_len);
        //写入新的文件内容
        fwrite($this->fp, $datastr);
        $my_datastr_len = strlen($datastr);
        unset($datastr);
        //销毁变量
        //新添文件目录信息
        $dirstr = "PK\1\2";
        $dirstr .= "\0\0";
        $dirstr .= "\24\0";
        $dirstr .= "\0\0";
        $dirstr .= "\10\0";
        $dirstr .= $hexdtime;
        $dirstr .= pack('V', $crc);
        $dirstr .= pack('V', $c_len);
        $dirstr .= pack('V', $unc_len);
        $dirstr .= pack('v', strlen($name));
        $dirstr .= pack('v', 0);
        $dirstr .= pack('v', 0);
        $dirstr .= pack('v', 0);
        $dirstr .= pack('v', 0);
        $dirstr .= pack('V', 32);
        $dirstr .= pack('V', $this->datastr_len);
        $dirstr .= $name;
        //目录信息
        $this->dirstr .= $dirstr;
        $this->file_count++;
        $this->dirstr_len += strlen($dirstr);
        $this->datastr_len += $my_datastr_len;
        //销毁变量
        unset($dirstr);
        return true;
    }
    /**
     * 返回文件的修改时间格式
     */
    private function unix2DosTime($unixtime = 0)
    {
        $timearray = $unixtime == 0 ? getdate() : getdate($unixtime);
        if ($timearray['year'] < 1980) {
            $timearray['year'] = 1980;
            $timearray['mon'] = 1;
            $timearray['mday'] = 1;
            $timearray['hours'] = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        }
        return $timearray['year'] - 1980 << 25 | $timearray['mon'] << 21 | $timearray['mday'] << 16 | $timearray['hours'] << 11 | $timearray['minutes'] << 5 | $timearray['seconds'] >> 1;
    }
    //释放压缩包资源
    private function createfile()
    {
        //压缩包结束信息,包括文件总数,目录信息读取指针位置等信息
        $endstr = "PK\5\6\0\0\0\0" . pack('v', $this->file_count) . pack('v', $this->file_count) . pack('V', $this->dirstr_len) . pack('V', $this->datastr_len) . "\0\0";
        fwrite($this->fp, $this->dirstr . $endstr);
        fclose($this->fp);
    }
    /**
     * 列出目录下所有文件
     * $dir_name ：目录名
     * @无返回值，直接将字符串信息保存到$Araay成员中，使用|符分割
     */
    private function ListFile($dir_name)
    {
        //抑制错误信息显示  便于自定义错误显示
        $dir_handle = opendir($dir_name);
        if (!$dir_handle) {
            die("目录打开错误！");
        }
        //文件名为‘0’时，readdir返回FALSE，判断返回值是否不全等
        while (false !== ($filename = readdir($dir_handle))) {
            //古怪的 is_dir($filename) ! $filename这个路径必须能够寻到！当$filename不存在或者不是目录时返回false
            if ($filename != '.' && $filename != '..') {
                //判断 是否为一个目录
                if (is_dir($dir_name . $filename)) {
                    //$dir_flag标志目录树层次
                    $this->ListFile($dir_name . $filename . '/');
                } else {
                    $array = $this->Array . $dir_name . $filename . '|';
                    $this->Array = $array;
                }
            }
        }
        //关闭目录句柄
        closedir($dir_handle);
    }
    /**
     * 输出压缩包下载
     */
    private function ZipDow($url)
    {
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        // 文件名
        header('Content-disposition: attachment; filename=' . basename($url));
        // 输出格式
        header('Content-Type: application/' . ltrim($this->ziptype, '.'));
        // 告诉浏览器，这是二进制文件
        header("Content-Transfer-Encoding: binary");
        // 告诉浏览器，文件大小
        header('Content-Length: ' . filesize($url));
        //输出文件;
        @readfile($url);
    }
}