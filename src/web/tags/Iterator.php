<?php
namespace swiftphp\core\web\tags;

use swiftphp\core\web\HtmlHelper;

/**
 * 遍历数据集合标签
 * 有已知的BUG:多个Iterator标签套嵌时,父标签会把子标签的相同字段覆盖;按现时的视图引擎渲染方式,此BUG无法消除.
 * @author Tomix
 *
 */
class Iterator extends TagBase
{
    /**
     * 数据源
     * @var array
     */
    protected $dataSource=[];

    /**
     * 状态标识
     * @var string
     */
    protected $status="status";

    /**
     * 设置数据源
     * @param array $value
     */
    public function setDataSource($value)
    {
        $this->dataSource=$value;
    }

    /**
     * 设置数据源(setDataSource的别名)
     * @param array $value
     */
    public function setData($value)
    {
        $this->dataSource=$value;
    }

    /**
     * 设置状态标识,默认为status
     * @param string $value
     */
    public function setStatus($value)
    {
        $this->status=$value;
    }

    /**
     * 获取标签渲染后的内容
     * @return string
     */
    public function getContent(&$outputParams=[])
    {
        if(!is_array($this->dataSource)){
            return "";
        }
        //根据数据源替换变量
        $builder=$this->buildTemplate($this->getInnerHtml(),$outputParams);

        //清空多余的占位
//         $pattern="/#\{[\w\.]+\}/isU";
//         $builder=preg_replace($pattern, "", $builder);

        return $builder;
    }

    private function buildTemplate($template,&$outParams=[])
    {
        //匹配参数
        $builder="";
        foreach ($this->dataSource as $line){
            $_template=$template;
            $_template=$this->buildTemplateRow($_template, $line, "/\\\${[\s]{0,}([^\s]{1,})[\s]{0,}}/isU",$outParams);
            $_template=$this->buildTemplateRow($_template, $line, "/#{[\s]{0,}([^\s]{1,})[\s]{0,}}/isU",$outParams);
            $builder.=$_template;
        }
        return $builder;
    }

    /**
     * 替换每数据行的模板
     * @param string $template
     * @param array|object $dataRow
     * @param string $pattern
     * @param array $outParams
     * @return mixed
     */
    private function buildTemplateRow($template,$dataRow,$pattern,&$outParams=[])
    {
        $matches=[];
        if(preg_match_all($pattern,$template,$matches)){
            for($i=0;$i<count($matches[0]);$i++){
                $search=$matches[0][$i];
                $paramKey=$matches[1][$i];
                $value=HtmlHelper::getUIParams($dataRow, $paramKey);

                if(is_array($value)||is_object($value)){
                    //数组或对象,附加到输出参数
                    $key=uniqid()."-".$paramKey;//唯一key
                    $outParams[$key]=$value;
                    //转为${key}表达式的全局参数
                    $template=str_replace($search, "\${".$key."}", $template);
                }else if(is_numeric($value)||is_string($value)){
                    //数字或字符串,替换
                    $template=str_replace($search, $value, $template);
                }
            }
        }
        return $template;
    }

    /**
     * 替换模板内容
     * @param array $data
     * @param string $template
     * @param array $parentRow
     * @param number $level
     * @return string
     */
    protected function ___buildTemplate($data,$template,$parentRow=null,$level=0)
    {
        $builder="";
        for($i=0;$i<count($data);$i++){
            $_template=$template;

            //状态信息行
            if(!empty($this->status)){
                $arr=["index"=>(string)$i
                    ,"count"=>(string)($i+1)
                    ,"first"=>($i==0)?"true":"false"
                    ,"odd"=>($i/2==0)?"true":"false"
                    ,"last"=>($i==count($data)-1)?"true":"false"];
                $statusTemplate="#".$this->status;
                foreach ($arr as $k=>$v){
                    $_template=str_replace($statusTemplate.".".$k, $v, $_template);
                }
            }

            //匹配上级字段#{_parent.[key]}
            if($parentRow!=null && is_array($parentRow))
            {
                foreach ($parentRow as $field=>$value){
                    $_template=preg_replace("/#{[\s]{0,}_parent.".$field."[\s]{0,}}/",$value,$_template);
                }
            }

            //当前行
            $row=$data[$i];
            if(is_object($row)){
                $row=get_object_vars($row);
            }
            if(is_array($row)){
                foreach ($row as $field=>$value){
                    if(!is_array($value) && !is_object($value)){
                        $value=str_replace("$", "\\\$", $value);//保护全局表达式
                        $_template=preg_replace("/#{[\s]{0,}".$field."[\s]{0,}}/",$value,$_template);
                    }else if(is_array($value)){
                        $_template=$this->addArrayParams($_template, $value, $field);
                    }else if(is_object($value)){
                        $value=get_object_vars($value);
                        $_template=$this->addArrayParams($_template, $value,$field);
                    }
                }
            }
            //$_template=preg_replace("/#{([0-9a-zA-Z_ ]{0,})}/","",$_template);
            $builder .= trim($_template);
        }
        return $builder;
    }

    /**
     * 取得子模板内容
     * 子模板没有隔行模板标签,所以只有一个模板行
     */
    protected function getTemplateChild($parentHtml)
    {
        //返回值数组:0,parent属性;1,outerHtml,2,innerHtml
        $tagName="template";
        $pattern="/<".$tagName."[^>]{1,}parent[\s]*=[\s]*[\"|\']([^\s]{1,})[\"|\'][^>]*>(.*)<\/".$tagName.">/is";//贪婪模式
        $matches=[];
        if(preg_match_all($pattern,$parentHtml,$matches,PREG_SET_ORDER)>0){
            $match=$matches[0];
            return ["outerHtml"=>$match[0],"parent"=>$match[1],"innerHtml"=>$match[2]];
        }
        return null;

    }


    protected function addArrayParams($template,$array,$prefix="")
    {
        foreach ($array as $key=>$value){
            $_prefix=$prefix.".".$key;
            if(is_array($value)){
                $template=$this->addArrayParams($template, $value,$_prefix);
            }else if(is_object($value)){
                $_value=get_object_vars($value);
                $template=$this->addArrayParams($template, $_value,$_prefix);
            }else{
                $value=str_replace("$", "\\\$", $value);
                $template=preg_replace("/#{[\s]{0,}".$prefix.".".(string)$key."[\s]{0,}}/",$value,$template);
            }
        }
        return $template;
    }

}

