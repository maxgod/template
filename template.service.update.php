<?php
/*
 *
 * 升级版本
 * 使用词法解析器进行处理 token_get_all
 *
 */
namespace SERVICE;
define('IS_IF',120);
define('IS_ELSE',121);
define('IS_ELSEIF',122);
define('IS_FOR',123);
define('IS_FOREACH',124);
define('IS_SWITCH',125);
define('IS_CASE',126);
define('IS_DEFAULT',127);
define('IS_ECHO',128);
define('IS_ENDIF',129);
define('IS_ENDFOR',130);
define('IS_ENDFOREACH',131);
define('IS_ENDSWITCH',132);
define('IS_BREAK',133);
define('IS_STRING',134);
define('IS_VARIABLE',135);
define('IS_DOLLAR',259);
define('IS_POINT',260);
define ('DOLLAR_OPEN_CURLY_BRACES', 261);
define ('PAAMAYIM_NEKUDOTAYIM', 262);
define ('IS_SEMICOLON', 263);
define ('IS_GTE', 265);
define ('IS_COLON', 267);
define ('IS_LEFT_BRACKETS', 268);
define ('IS_RIGHT_BRACKETS', 269);
define ('IS_AS', 270);



class templateServiceUpdate
{
    //开始标签
    public $labLeft = "{";
    //结束标签
    public $labRight = "}";
    public $symbol = array(IS_DOLLAR=>"\$",IS_POINT=>"->",DOLLAR_OPEN_CURLY_BRACES=>"<?php ",PAAMAYIM_NEKUDOTAYIM=>" ?>",IS_SEMICOLON=>";",264=>",",IS_GTE=>" => ",266=>"=",IS_COLON=>":",IS_LEFT_BRACKETS=>"(",IS_RIGHT_BRACKETS=>")",IS_AS=>" as ");
    public $word = array(IS_IF=>'if',IS_ELSE=>'else',IS_ELSEIF=>'elseif',IS_FOR=>'for',IS_FOREACH=>'foreach',IS_SWITCH=>'switch',IS_CASE=>'case',IS_DEFAULT=>'default',IS_ECHO=>'echo',IS_ENDIF=>'endif',IS_ENDFOR=>'endfor',IS_ENDFOREACH=>'endforeach',IS_ENDSWITCH=>'endswitch',IS_BREAK=>'break');
    public $search = array('eq'=>'==','gt'=>'>','gte'=>'>=','lt'=>'<','lie'=>'<=','neq'=>'<>','mod'=>'%','not'=>'~','by'=>'/','and'=>'&&','or'=>'||');
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
                $Replace[] = $this->symbol[DOLLAR_OPEN_CURLY_BRACES].$this->keyword_handle($lexical).$this->symbol[PAAMAYIM_NEKUDOTAYIM];
            }
            else
            {
                //非词法处理
                $Replace[] = $this->symbol[DOLLAR_OPEN_CURLY_BRACES].$this->handle($lexical).$this->symbol[PAAMAYIM_NEKUDOTAYIM];
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
                        if(!substr_count($val,$this->symbol[IS_DOLLAR]) && !isset($this->search[$val]) && ($ascii < 33 || $ascii > 64) && $key )
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
                case IS_FOREACH:
                    if($count == 1)
                        return "/* FOREACH 语法错误 */";
                    foreach($lexical as $k=>$val)
                    {
                        $parameters .= $val;
                        $parameters .= $k == 1?$this->symbol[IS_AS]:$this->symbol[IS_GTE];
                    }
                    $parameters = rtrim($parameters,$this->symbol[IS_GTE]);
                    break;
                case IS_FOR:
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
            if($key <> IS_CASE)
                $string = "{$this->word[$key]}{$this->symbol[IS_LEFT_BRACKETS]}{$parameters}{$this->symbol[IS_RIGHT_BRACKETS]}{$this->symbol[IS_COLON]}";
            else
                $string = "{$this->word[$key]} {$parameters}{$this->symbol[IS_COLON]}";

        }
        else
        {
            $string = "{$this->word[$key]}{$this->symbol[IS_SEMICOLON]}";
        }
        return $string;

    }
    /*
     * 普通处理方式
     *
     */
    public function handle($lexical)
    {
        if(substr_count($lexical[0],$this->symbol[IS_DOLLAR]) && !substr_count($lexical[0],$this->symbol[IS_DOLLAR]."this".$this->symbol[IS_POINT]))
        {
            $string = implode(" ",$lexical);
            return $string.$this->symbol[IS_SEMICOLON];
        }
        else
        {
            $function = $lexical[0];
            unset($lexical[0]);
            if(!empty($lexical))
            {
                $string = implode(",",$lexical);
                return $function.$this->symbol[IS_LEFT_BRACKETS].$string.$this->symbol[IS_RIGHT_BRACKETS].$this->symbol[IS_SEMICOLON];
            }
            else
            {
                return $function.$this->symbol[IS_SEMICOLON];
            }
        }
    }

}