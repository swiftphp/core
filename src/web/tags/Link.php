<?php
namespace swiftphp\core\web\tags;

/**
 * 链接标签
 * @author Tomix
 *
 */
class Link extends TagBase
{
    /**
     * 获取标签渲染后的内容
     * {@inheritDoc}
     * @see \swiftphp\core\web\tags\TagBase::getContent()
     */
    public function getContent()
    {
        $str="<a";
        foreach ($this->getAttributes() as $key => $val){
            $str.=" ".$key."=\"".$val."\"";
        }
        $str.=">".$this->getInnerHtml()."</a>";
        return $str;
    }
}

