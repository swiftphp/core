<?php
namespace swiftphp\core\web\out;

use swiftphp\core\http\IOutput;
use swiftphp\core\web\View;
use swiftphp\core\utils\StringUtil;
use swiftphp\core\web\HtmlHelper;
use swiftphp\core\web\ITag;
use swiftphp\core\utils\SecurityUtil;

/**
 * 视图引擎输出
 * @author Tomix
 *
 */
class HtmlView extends View implements IOutput
{
    /**
     * 默认模板基本目录
     * @var string
     */
    protected $m_defaultViewDir="views";

    /**
     * 注册的标签库
     * @var array
     */
    protected $m_taglibs=["php"=>"swiftphp\\core\\web\\tags"];

    /**
     * 标签统计处理标记
     * @var string
     */
    protected $m_tagPlaceHolder="run-on-server-tag";

    /**
     * 输出内容
     * @var string
     */
    private $m_outputContent="";

    /**
     * 模板需要的参数
     * @var array
     */
    private $m_onlyViewParams=[];

    /**
     * 输出
     * {@inheritDoc}
     * @see \swiftphp\core\http\IOutput::output()
     */
    public function output()
    {
        if(empty($this->m_outputContent))
            $this->m_outputContent=$this->getContent();
            $content=$this->m_outputContent;
            $cacheFile=$this->getRuntimeDir()."/".md5($content);
            if(!file_exists($cacheFile)){
                file_put_contents($cacheFile, $content);
            }
            require_once $cacheFile;
    }

    /**
     * 获取视图被渲染后的完整输出内容
     */
    public function getContent()
    {
        //读取视图内容;注意模板或部件文件的更新不会自动清空缓存
        $viewFile=$this->searchView();
        if(!file_exists($viewFile)){
            throw new \Exception("View file '".$this->m_viewFile."' does not exist.");
        }
        $view=file_get_contents($viewFile);
        $view=StringUtil::removeUtf8Bom($view);

        //标签树信息
        $tagTree=[];

        //合并视图的模板与部件,预处理标签
        $viewCacheFile=$this->m_runtimeDir."/".md5($view);
        $tagInfoCacheFile=$this->m_runtimeDir."/".md5(md5($view));
        $tagLibInfoCacheFile=$this->m_runtimeDir."/".md5(md5(md5($view)));
        if(!$this->m_debug && file_exists($viewCacheFile) && file_exists($tagInfoCacheFile)){
            //从缓存文件读取
            $view=file_get_contents($viewCacheFile);
            $tagTree=unserialize(file_get_contents($tagInfoCacheFile));

            //taglibs
            if(file_exists($tagLibInfoCacheFile)){
                $this->m_taglibs=unserialize(file_get_contents($tagLibInfoCacheFile));
            }

        }else{
            //重新处理,并写到缓存文件
            $view=$this->loadTemplate($view,dirname($viewFile),$tagTree,$this->m_taglibs);
            file_put_contents($viewCacheFile, $view);
            file_put_contents($tagInfoCacheFile, serialize($tagTree));
            file_put_contents($tagLibInfoCacheFile, serialize($this->m_taglibs));
        }

        //预处理视图参数(抽取模板需要的参数),为后面替换参数的过程加速
        $this->m_onlyViewParams = $this->preLoadViewParams($view);

        //替换参数与标签
        $view=$this->applyView($view,$tagTree);

        //清除空参数与标签
        $view=preg_replace("/\\\${[^}]*}/","",$view);

        //返回
        $this->m_outputContent=$view;
        return $view;
    }

    /**
     * 预处理视图参数(把模板需要的参数另存到临时变量)
     * @param string $view
     */
    protected function preLoadViewParams($view)
    {
        $pattern="/\\\${([^}]{1,})}/";
        $matches=[];
        preg_match_all($pattern, $view,$matches);
        $params=[];
        if(count($matches)>0){
            foreach ($matches[1] as $param){
                if(!in_array($param, $params)){
                    $params[]=$param;
                }
            }
        }
        return $params;
    }

    /**
     * 替换变量,标签
     * @param string $view
     */
    protected function applyView($view,$tagInfo=[])
    {
        //替换标签(必须先处理标签,否则标签树的位置对应不上)
        $view=$this->loadTags($view,$tagInfo);

        //替换变量
        $view=$this->loadParams($view);

        return $view;
    }

    /**
     * 替换标签
     * @param string $view
     * @return string
     */
    protected function loadTags($view,$tags=[])
    {
        //所有标签的信息
        $tagObjs=[];
        $tagPlaceHolderLen=strlen($this->m_tagPlaceHolder);
        foreach ($tags as $tag){
            $outerHtml=substr($view, $tag["start"],$tag["end"]-$tag["start"]+$tagPlaceHolderLen+3);
            $pos=strpos($outerHtml, ">");
            $innerHtml=substr($outerHtml, $pos+1,strrpos($outerHtml, "<")-$pos-1);
            $attrs=HtmlHelper::getTagAttributes($outerHtml);
            if(!array_key_exists("_tag", $attrs))
                continue;
                $types=explode(":",$attrs["_tag"]);
                $class=$this->m_taglibs[$types[0]]."\\".ucfirst($types[1]);
                if(!class_exists($class)){
                    throw new \Exception("Call to undefined tag '".$attrs["_tag"]."'");
                }
                $obj=new $class();
                if(!($obj instanceof ITag)){
                    throw new \Exception("Tag '".$attrs["_tag"]."' not implements swiftphp\core\\web\\ITag");
                }

                //$obj->setInnerHtml($innerHtml);
                foreach ($attrs as $name=>$value){
                    if($name!="_tag" && $name!="_id"){
                        //bool类型转换
                        if(strtolower($value)=="true"){
                            $value=1;
                        }else if(strtolower($value)=="false"){
                            $value=0;
                        }

                        //匹配动态参数
//                         $matches=[];
//                         if(preg_match("/\\\${[\s]{0,}([^\s]{1,})[\s]{0,}}/isU",$value,$matches)>0){
//                             $paramKey=$matches[1];
//                             //$value=$this->getUIParams($this->m_tagParams,$paramKey);
//                             $_value=$this->getUIParams($this->m_tagParams,$paramKey);
//                             if(!is_array($_value) && !is_object($_value)){
//                                 $value=str_replace($matches[0], $_value, $value);
//                             }else{
//                                 $value=$_value;
//                             }
//                         }

                        //匹配动态参数(modified@2018-4-20,修改可以匹配多个参数)
                        $matches=[];
                        if(preg_match_all("/\\\${[\s]{0,}([^\s]{1,})[\s]{0,}}/isU",$value,$matches)){
                            $holders=$matches[0];
                            $keys=$matches[1];
                            for($i=0;$i<count($keys);$i++){
                                $key=$keys[$i];
                                $_value=$this->getUIParams($this->m_tagParams,$key);
                                if(!is_array($_value) && !is_object($_value)){
                                    $holder=$holders[$i];
                                    $value=str_replace($holder, $_value, $value);
                                }else{
                                    //取出来的值为对象或数组,则忽略后面的参数
                                    $value=$_value;
                                    break;
                                }
                            }
                        }

                        //注入属性
                        $setter="set".ucfirst($name);
                        if(method_exists($obj, $setter)){
                            $obj->$setter($value);
                        }else{
                            $obj->addAttribute($name, $value);
                        }
                    }
                }

                //parent
                $parentId="";
                if(!empty($tag["parent"])){
                    $ptag=$tag["parent"];
                    $_outerHtml=substr($view, $ptag["start"],$ptag["end"]-$ptag["start"]+strlen($this->m_tagPlaceHolder)+3);
                    $_attrs=HtmlHelper::getTagAttributes($_outerHtml);
                    $parentId=$_attrs["_id"];
                }

                $tagObjs[$attrs["_id"]]=["tag"=>$obj,"innerHtml"=>$innerHtml,"outerHtml"=>$outerHtml,"parentId"=>$parentId];
        }

        //从后向前开始替换标签内容
        $tagObjs=array_reverse($tagObjs);
        foreach ($tagObjs as $tag){
            $obj=$tag["tag"];
            $innerHtml=$tag["innerHtml"];
            $innerHtml=$this->loadParams($innerHtml);
            $obj->setInnerHtml($innerHtml);
            $html=$obj->getContent();
            $outerHtml=$tag["outerHtml"];
            $view=str_replace($outerHtml, $html, $view);

            //replace all parents' html
            $parentId=$tag["parentId"];
            while (!empty($parentId)){
                if(array_key_exists($parentId, $tagObjs)){
                    $parent=$tagObjs[$parentId];
                    $parent["innerHtml"]=str_replace($outerHtml, $html, $parent["innerHtml"]);
                    $parent["outerHtml"]=str_replace($outerHtml, $html, $parent["outerHtml"]);
                    $tagObjs[$parentId]=$parent;
                    //                     var_dump($tagObjs);
                    //                     exit;
                    $parentId=$tagObjs[$parentId]["parentId"];
                }else{
                    $parentId="";
                }
            }
        }
        return $view;
    }

    /**
     * 替换变量
     * @param string $view
     */
    protected function loadParams($view)
    {
        //从视图参数取值填充占位符
        foreach ($this->m_onlyViewParams as $param){
            $keys=explode(".", $param);
            $value=null;

            //取第一个值(第一个值必定是索引数组)
            $key=$keys[0];
            if(array_key_exists($key, $this->m_viewParams)){
                $value=$this->m_viewParams[$key];
            }

            //迭代取值
            if(count($keys)>1){
                for($i=1;$i<count($keys);$i++){
                    $key=$keys[$i];
                    if(is_object($value)){
                        $value=get_object_vars($value);
                    }
                    if(array_key_exists($key, $value)){
                        $value=$value[$key];
                    }
                }
            }

            //替换占位符(非空&&值类型||空字符串)
            if((!empty($value) && !is_array($value) && !is_object($value))||$value==""){
                $value=str_replace("$", "\\\$", $value);
                $view=preg_replace("/\\\${[\s]{0,}".$param."[\s]{0,}}/",$value,$view);

            }
        }

        //         foreach($this->m_viewParams as $name=>$value){
        //             //替換输出参数
        //             if(!is_array($value) && !is_object($value)){
        //                 //值类型
        //                 $value=str_replace("$", "\\\$", $value);
        //                 $view=preg_replace("/\\\${[\s]{0,}".$name."[\s]{0,}}/",$value,$view);
        //             }else if(is_array($value)){
        //                 //数组类型:${paramKey.$key}
        //                 $view=$this->addArrayViewParams($view, $value,$name);
        //             }else if(is_object($value)){
        //                 //对象类型:${paramKey.$property}
        //                 $value=get_object_vars($value);
        //                 $view=$this->addArrayViewParams($view, $value,$name);
        //             }else{
        //                 //$view=preg_replace("/\\\\${[\s]{0,}".$name."[\s]{0,}\}/","%object%@".$name,$view);
        //             }
        //         }
        return $view;
    }

    /**
     * 预处理视图:混合视图的模板与部件,预处理标签信息
     * @param string $view 预先读取的主模板内容
     * @param string $relDir 模板所在目录
     * @return string
     */
    protected function loadTemplate($view,$relDir,&$tagTree=[],&$taglibs=[])
    {
        //标签库:<taglib prefix="php" namespace="swiftphp\core\web\tags" />
        //模板标签:<page:template file="" />
        //部件标签:<page:part file="" />
        //占位标签:<page:contentHolder id="" />
        //内容标签:<page:content id="" />

        //<taglib prefix="php" namespace="swiftphp\core\web\tags" />
        //读取标签库后,清空标签库标签
        $pattern="/<taglib[^>]{1,}\/>/i";
        $matches=[];
        if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
            foreach($matches as $match){
                $outHtml=$match[0];
                $view=str_replace($outHtml, "", $view);
                $attrs=HtmlHelper::getTagAttributes($match[0]);
                $libPrifix=trim($attrs["prefix"]);
                if(!array_key_exists($libPrifix, $taglibs)){
                    $taglibs[$libPrifix]=trim($attrs["namespace"]);
                }
            }
        }

        //模板标签:<page:template file="" />;一个视图最多只存在一个模板
        $pattern="/<page:template[^>]{1,}file[\s]*=[\s]*[\"|\']([^\s<>\"\']{1,})[\"|\'][^>]*>/i";
        $matches=[];
        if(preg_match($pattern,$view,$matches)>0){
            $templateFile=$matches[1];
            $templateFile=$relDir."/".$templateFile;
            if(!file_exists($templateFile) || !is_file($templateFile)){
                throw new \Exception("Template file '".$matches[1]."' does not exist");
            }
            $template=file_get_contents($templateFile);
            $template=StringUtil::removeUtf8Bom($template);
            $view=$this->addTemplateToView($template,$view);
        }

        //部件标签:<page:part file="" />
        $pattern="/<page:part[^>]{1,}file[\s]*=[\s]*[\"|\']([^\s<>\"\']{1,})[\"|\'][^>]*>/i";
        $matches=[];
        if(preg_match_all($pattern,$view,$matches)>0){
            $parts=$matches[0];
            $tpls=$matches[1];
            for($i=0;$i<count($parts);$i++){
                $part=$parts[$i];
                $tpl=$tpls[$i];
                $tpl=$relDir."/".$tpl;

                $tplHtml="";
                if(file_exists($tpl) && is_file($tpl)){
                    $tplHtml=file_get_contents($tpl);
                }
                $tplHtml=StringUtil::removeUtf8Bom($tplHtml);
                //$tplHtml=$this->applyView($tplHtml);
                $view=str_replace($part, $tplHtml, $view);
            }
        }

        //标签预处理
        foreach (array_keys($this->m_taglibs) as $prefix){
            //单标签转为双标签
            $pattern="/<".$prefix.":([\\w]{1,})\s[^>]*\/>/isU";
            $matches=[];
            if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
                foreach($matches as $match){
                    $html=$match[0];
                    $tag=$match[1];
                    $_html=trim(substr($html, 0,strrpos($html, "/>")))."></".$prefix.":".$tag.">";
                    $view=str_replace($html, $_html, $view);
                }
            }

            //统一处理为通用的标签前缀
            $pattern="/<".$prefix.":([\\w]{1,})\s[^>]*>/isU";
            $matches=[];
            if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
                foreach($matches as $match){
                    $html=$match[0];
                    $tag=$match[1];

                    $search="/<".$prefix.":".$tag."/";
                    $replace="<".$this->m_tagPlaceHolder." _tag=\"".$prefix.":".$tag."\" _id=\"".SecurityUtil::newGuid()."\"";
                    $view=preg_replace($search, $replace, $view,1);

                    $search="</".$prefix.":".$tag.">";
                    $replace="</".$this->m_tagPlaceHolder.">";
                    $view=str_replace($search, $replace, $view);
                }
            }
        }

        $tagTree=$this->loadTagTree($view);

        return $view;
    }

    /**
     * 搜索标签树
     * @param string $template
     */
    private function loadTagTree($template)
    {
        $open="<".$this->m_tagPlaceHolder;
        $close="</".$this->m_tagPlaceHolder.">";

        //搜索标签起止位置
        $tagpos=[];
        $offset=strpos($template, $open);
        while ($offset>0){
            //echo $offset."----------------\r\n".substr($template, $offset)."\r\n";
            $tagpos[$offset]=1;
            $offset=strpos($template, $open,$offset+1);
        }
        $offset=strpos($template, $close);
        while ($offset>0){
            //echo $offset."----------------\r\n".substr($template, $offset)."\r\n";
            $tagpos[$offset]=2;
            $offset=strpos($template, $close,$offset+1);
        }

        //配对标签
        ksort($tagpos);
        $tags=[];
        $count=0;
        $_count=-1;
        while ($count>$_count)
        {
            $count=count($tagpos);
            $keys=array_keys($tagpos);
            for($i=0;$i<count($keys);$i++){
                if($i>=count($keys)-1){
                    break;
                }
                $k1=$keys[$i];
                $k2=$keys[$i+1];
                if($tagpos[$k1]==1 && $tagpos[$k2]==2){
                    //echo $tagpos[$k1].":".$tagpos[$k2]."--------\r\n";
                    //$tags[$tagpos[$k1]]=new TagInfo();
                    $tag=["start"=>$k1,"end"=>$k2,"parent"=>null];
                    $tags[$k1]=$tag;
                    //$tags[$k1]=["x"=>$k1,"y"=>$k2];
                    unset($tagpos[$k1]);
                    unset($tagpos[$k2]);
                    $i++;
                }
            }
            $_count=count($tagpos);
        }

        //搜索父标签
        ksort($tags);
        $tags=array_values($tags);
        for($i=0;$i<count($tags);$i++){
            $tag=$tags[$i];
            for($j=count($tags)-1;$j>=0;$j--){
                $_tag=$tags[$j];
                if($_tag["start"] < $tag["start"] && $_tag["end"] > $tag["end"]){
                    $tags[$i]["parent"]=$_tag;
                    break;
                }
            }
        }
        //         var_dump($tags);
        //         exit;
        return $tags;
    }

    /**
     * 把母板内容合并到视图
     * @param $template 模板内容
     * @param $view	视图内容
     * @return void
     */
    protected function addTemplateToView($template,$view)
    {
        $holders = $this->getTemplateContentHolders($template);
        $contents=$this->getViewContents($view);
        foreach(array_keys($contents) as $id){
            if(!empty($holders[$id])){
                $template=str_replace($holders[$id],trim($contents[$id]),$template);
                unset($holders[$id]);
            }
        }
        foreach($holders as $holder){
            $template=str_replace($holder,"",$template);
        }
        return $template;
    }

    /**
     * 取模板内容占位模板
     * 占位标签:<page:contentHolder id="" />
     * @param $template 模板内容
     * @return array
     */
    protected function getTemplateContentHolders($template)
    {
        $holders=[];
        $pattern="/<page:contentHolder[^>]{1,}id[\s]*=[\s]*[\"|\']([^\s]{1,})[\"|\'][^>]*(\/>|>[^>]*<\/php:contentHolder>)/i";
        $matches=[];
        if(preg_match_all($pattern,$template,$matches,PREG_SET_ORDER)>0){
            foreach($matches as $match){
                if(count($match)>=2){
                    $holders[$match[1]]=$match[0];
                }
            }
        }
        return $holders;
    }

    /**
     * 取得视图占位内容
     * 内容标签:<page:content id="" />
     * 注:内容控件不能这样写<page:content id="header" />
     * @param $view 视图
     * @return array
     */
    protected function getViewContents($view)
    {
        $contents=[];
        $pattern="/<page:content[^>]{1,}id[\s]*=[\s]*[\"|\']([^\s]{1,})[\"|\'][^>]*>(.*)<\/page:content>/isU";
        $matches=[];
        if(preg_match_all($pattern,$view,$matches,PREG_SET_ORDER)>0){
            foreach($matches as $match){
                //$match:0,所有内容;1,id;2,内部内容
                if(count($match)>=2){
                    $contents[$match[1]]=$match[2];
                }
            }
        }
        return $contents;
    }

    /**
     * 替换数组变量
     * @param unknown $template
     * @param unknown $array
     * @param string $prefix
     * @return mixed
     */
    protected function addArrayViewParams($template,$array,$prefix="")
    {
        if(is_array($array)){
            foreach ($array as $key=>$value){
                $_prefix=$prefix.".".$key;
                if(is_array($value)){
                    $template=$this->addArrayViewParams($template, $value,$_prefix);
                }else if(is_object($value)){
                    $_value=get_object_vars($value);
                    $template=$this->addArrayViewParams($template, $_value,$_prefix);
                }else{
                    $value=str_replace("$", "\\\$", $value);
                    $template=preg_replace("/\\\${[\s]{0,}".$prefix.".".(string)$key."[\s]{0,}}/",$value,$template);
                }
            }
        }
        return $template;
    }


    /**
     * 搜索模板文件
     */
    protected function searchView()
    {
        /*
         * 根目录: 相对于配置文件位置定义为根目录.
         * 搜索顺序: 区域根目录->根目录
         *1:以/起的路径:相对于根目录,不需要搜索.
         *2:不以/起的带路径:按搜索顺序.
         */

        //根目录: 相对于配置文件位置定义为根目录.
        $rootDir=rtrim($this->m_config->getBaseDir(),"/");

        //以/起的路径:相对于根目录,不需要搜索.
        if(strpos($this->m_viewFile, "/")===0){
            return $rootDir.$this->m_viewFile;
        }

        //区域,控制器,操作
        $areaDir=trim($this->m_controller->getAreaName(),"/");
        $controllerBaseName=get_class($this->m_controller);
        $controllerBaseName=substr($controllerBaseName,strrpos($controllerBaseName, "\\")+1);
        $controllerBaseName=substr($controllerBaseName, 0,strpos($controllerBaseName, "Controller"));
        $actionName=$this->m_controller->getActionName();

        //需要搜索文件
        $searchFiles=[];
        if(empty($this->m_viewFile)){
            //如果没有定义,则按{控制器}/{操作}搜索
            $searchFiles[]=$controllerBaseName."/".$actionName.".html";
            $searchFiles[]=$controllerBaseName."/".StringUtil::toUnderlineString($actionName).".html";
            $searchFiles[]=lcfirst($controllerBaseName)."/".$actionName.".html";
            $searchFiles[]=lcfirst($controllerBaseName)."/".StringUtil::toUnderlineString($actionName).".html";
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".$actionName.".html";
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".StringUtil::toUnderlineString($actionName).".html";
        }else if(strpos($this->m_viewFile, "/")===false){
            //不包含目录时,添加{控制器}作为目录
            $searchFiles[]=$controllerBaseName."/".$this->m_viewFile;
            $searchFiles[]=lcfirst($controllerBaseName)."/".$this->m_viewFile;
            $searchFiles[]=StringUtil::toUnderlineString($controllerBaseName)."/".$this->m_viewFile;
        }else{
            $searchFiles[]=$this->m_viewFile;
        }

        //搜索顺序
        foreach ($searchFiles as $file){
            if(!empty($areaDir)){
                $_file=$rootDir."/".$areaDir."/".$this->m_defaultViewDir."/".$file;
                if(file_exists($_file)){
                    return $_file;
                }
            }
            $_file=$rootDir."/".$this->m_defaultViewDir."/".$file;
            if(file_exists($_file)){
                return $_file;
            }
        }
    }

    /**
     * 获取参数值
     * @param array $paramValues
     * @param string $paramKey
     * @return mixed
     */
    private function getUIParams($inputValues, $paramKey)
    {
        if(array_key_exists($paramKey, $inputValues)){
            return $inputValues[$paramKey];
        }else if(strpos($paramKey, ".")>0){
            $keys=explode(".", $paramKey);
            $value=$this->getUIParams($inputValues,$keys[0]);//根据第一段取得对象或数组值

            //key1.key2.key3...
            for($i=1;$i<count($keys);$i++){
                //对象转为数组
                if(is_object($value)){
                    $value=get_object_vars($value);
                }
                if(is_array($value)){
                    $key=$keys[$i];
                    $value=$value[$key];
                }
            }
            return $value;
        }
        return false;
    }

}