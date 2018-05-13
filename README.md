### PHP 目录、文件打包压缩解压类-支持所有压缩包类型

### 使用composer进行安装
~~~
composer require hulang/php-zip
~~~

### 使用composer进行更新
~~~
composer update hulang/php-zip
~~~

### 引入类文件
~~~
use hulang/Zip;
~~~

### 使用说明
~~~
//实例化Zip类
$zip = new Zip();
//单文件打包
$zip->SaveZip('./','./index.php','Ceshi','zip',false); 
//SaveZip参数详解
//$zip->SaveZip('压缩包保存目录','需要打包的目录或文件','压缩包的名称，默认为时间戳','压缩包的类型，默认为zip','是否弹出下载，默认为false');
//目录打包
$zip-> SaveZip('./','./','','rar',true);
//执行解压
$zip->UnZip('./Ceshi.zip','./1/',true,false);
//Unzip参数详解
//$zip->UnZip('压缩包完整路径','压缩路径','是否覆盖存在的文件，默认true','是否删除压缩包,默认false');
~~~