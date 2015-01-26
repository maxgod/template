<?php
/*
 *
 * php模板语言替换
 *
 */
namespace SERVICE;
define ('T_COLON', 267);
define ('T_LEFT_BRACKETS', 268);
define ('T_RIGHT_BRACKETS', 269);

class templateServiceUpdate
{
    //开始标签
    public $labLeft = "{";
    //结束标签
    public $labRight = "}";
    public $symbol = array(T_OBJECT_OPERATOR=>"->",T_OPEN_TAG=>"<?php ",T_CLOSE_TAG=>" ?>",T_SR=>";",264=>",",T_DOUBLE_ARROW=>" => ",266=>"=",T_COLON=>":",T_LEFT_BRACKETS=>"(",T_RIGHT_BRACKETS=>")",T_AS=>" as ");
    public $word = array(T_IF=>'if',T_ELSE=>'else',T_ELSEIF=>'elseif',T_FOR=>'for',T_FOREACH=>'foreach',T_SWITCH=>'switch',T_CASE=>'case',T_DEFAULT=>'default',T_ECHO=>'echo',T_ENDIF=>'endif',T_ENDFOR=>'endfor',T_ENDFOREACH=>'endforeach',T_ENDSWITCH=>'endswitch',T_BREAK=>'break');
    public $search = array('dec'=>'--','inc'=>'==','eq'=>'==','gt'=>'>','gte'=>'>=','lt'=>'<','lie'=>'<=','neq'=>'<>','mod'=>'%','not'=>'~','by'=>'/','and'=>'&&','or'=>'||');
    /**
     * 语言处理
     *
     * @param string $html 内容
     * @param bool $is_cache 是否开启缓存
     * @return string $html
     */
    public function Lexical($html = "",$is_cache = false)
    {
        @preg_match_all("/{$this->labLeft}(.*?){$this->labRight}/is",$html,$result);
        if(empty($result[0]))
        {
            return $html;
        }
        $result[0] = array_unique($result[0]);
        $result[1] = array_unique($result[1]);
        $Replace = $Keywords = array();
        foreach($result[1] as $key=>$val)
        {
            //进行词法解析
            $lexical = $this->Lexical_get_all(trim($val));
            if(isset($lexical['key']))
            {
               //词法处理
                $Replace[] = $this->symbol[T_OPEN_TAG].$this->keyword_handle($lexical).$this->symbol[T_CLOSE_TAG];
            }
            else
            {
                //非词法处理
                $Replace[] = $this->symbol[T_OPEN_TAG].$this->handle($lexical).$this->symbol[T_CLOSE_TAG];
            }
        }
        $html = str_replace($result[0],$Replace,$html);
        echo $html;
        return $html;
    }
    /**
     * 词法分析
     *  if|else|elseif|for|foreach|switch|case|default|echo|endif|endfor|endforeach|endswitch|break
     *  120|121|122 |  123|  124  |125   |126 | 127   |128 |129  |130   |131       |132      |133
     * 120-129   123-130  124-131 125-132
     * @param string $string 内容
     * @return array
     */
    public function Lexical_get_all($string)
    {
        $array = explode(" ",$string);
        $tokens = array();
        if(!empty($array))
        {
            foreach($array as $key=>$val)
            {
                if(is_numeric($val) || (is_string($val) && $val ) )
                {
                    if(array_search($val,$this->word))
                    {
                        $tokens['key'] = array_search($val,$this->word);
                        $tokens[$key] = $val;
                    }
                    else
                    {
                        $ascii = ord($val);
                        if(!substr_count($val,"\$") && !isset($this->search[$val]) && ($ascii < 33 || $ascii > 64) && $key )
                        {
                            $val = "'{$val}'";
                        }
                        elseif(isset($this->search[$val]))
                        {
                            $val = $this->search[$val];
                        }
                        $tokens[$key] = $val;
                    }
                }
            }
        }
        return $tokens;
    }
    /*
     * 词法解析方式
     *
     *
     */
    public function keyword_handle($lexical)
    {
        $key = $lexical['key'];
        unset($lexical[0]);
        unset($lexical['key']);
        $string = "";
        $parameters = "";
        $count = count($lexical);
        if($count)
        {
            switch($key)
            {
                case T_FOREACH:
                    if($count == 1)
                        return "/* FOREACH 语法错误 */";
                    foreach($lexical as $k=>$val)
                    {
                        $parameters .= $val;
                        $parameters .= $k == 1?$this->symbol[T_AS]:$this->symbol[T_DOUBLE_ARROW];
                    }
                    $parameters = rtrim($parameters,$this->symbol[T_DOUBLE_ARROW]);
                    break;
                case T_FOR:
                    if($count == 1)
                        return "/* FOR 语法错误 */";
                    $lexical = implode("",$lexical);
                    $i = 0;
                    foreach(str_split($lexical) as $k=>$val)
                    {
                        $ascii = ord($val);
                        if($ascii == 36 && $k)
                        {
                            $i++;
                            $chr[] = chr('59');
                        }
                        $chr[] = $val;
                    }
                    if($i<2)
                        $chr[] = chr('59');
                    $parameters = implode('',$chr);
                    break;
                default:
                    $parameters = implode(" ",$lexical);
                break;
            }
            if($key <> T_CASE)
                $string = "{$this->word[$key]}{$this->symbol[T_LEFT_BRACKETS]}{$parameters}{$this->symbol[T_RIGHT_BRACKETS]}{$this->symbol[T_COLON]}";
            else
                $string = "{$this->word[$key]} {$parameters}{$this->symbol[T_COLON]}";

        }
        else
        {
            $string = "{$this->word[$key]}{$this->symbol[T_SR]}";
        }
        return $string;

    }
    /*
     * 普通处理方式
     *
     */
    public function handle($lexical)
    {
        if(substr_count($lexical[0],"\$") && !substr_count($lexical[0],"\$this".$this->symbol[T_OBJECT_OPERATOR]))
        {
            $string = implode(" ",$lexical);
            return $string.$this->symbol[T_SR];
        }
        else
        {
            $function = $lexical[0];
            unset($lexical[0]);
            if(!empty($lexical))
            {
                $string = implode(",",$lexical);
                return $function.$this->symbol[T_LEFT_BRACKETS].$string.$this->symbol[T_RIGHT_BRACKETS].$this->symbol[T_SR];
            }
            else
            {
                return $function.$this->symbol[T_SR];
            }
        }
    }

}
