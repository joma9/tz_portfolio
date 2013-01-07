<?php
/*------------------------------------------------------------------------

# TZ Portfolio Extension

# ------------------------------------------------------------------------

# author    DuongTVTemPlaza

# copyright Copyright (C) 2012 templaza.com. All Rights Reserved.

# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL

# Websites: http://www.templaza.com

# Technical Support:  Forum - http://templaza.com/Forum

-------------------------------------------------------------------------*/

 //no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.factory');
jimport('joomla.html.editor');

class ArticleHTML
{

    public function renderImg($src,$width = null,$height = null,$crop = null,$title = null,$alt = null,$attribute = null){
        $_width     = null;
        $_height    = null;
        $_crop      = null;
        
        if($width)
            $_width  = '&width='.$width;
        if($height)
            $_height = '&height='.$height;
        if($crop)
            $_crop = '&cropratio='.$crop;
        if($title)
            $title  = ' title="'.$title.'"';
        if($alt)
            $alt    = ' alt="'.$alt.'"';

        if($width || $height){
            $src    = JURI::root().'components/com_tz_portfolio/image.php?image='.$src.$_width.$_height.$_crop;
        }

        $html   = '<img src="'.$src.'"'.$title.$alt.$attribute.'>';

        return $html;
    }

    // extra text field, date
    public function renderTextField($name,$value=null,$id=null,$javascript=null){
        $html   = '<input type="text" name="'.$name.'" value="'.$value.'"'
                  .(($id)?' id="'.$id.'"':'')
                  .(($javascript)?' '.$javascript:'')
                  .'>';
        return $html;
    }

    // extra text area
    public function renderTextArea($name,$value=null,$id=null,$_editor=null,$width=null,$height=null,$rows=null,$col=null,$button=true,$javascript=null){

        $rows   = ($rows)?$rows:'5';
        $col    = ($col)?$col:'5';
        $id     = ($id)?' id = '.$id:'';
        $javascript = ' '.($javascript)?$javascript:' ';
        $asset = null;
        $author = null;
        $params = array();

//        if(!empty($_editor)){
//            $id = ($id)?' id = '.$id:$name;
//            //var_dump($id);
//            $editor = &JFactory::getEditor();
//            $html   = $editor -> display($name,$value,$width,$height,$col,$rows,$button,$id,$asset,$author,$params);
//        }
//        else{
            $html   = '<textarea name="'.$name.'" rows="'.$rows.'" cols="'.$col.'"'
                      .$id
                      .$javascript.'>'.$value.'</textarea>';
//        }
        return $html;
    }

    // extra drop down select, multiple select
    public function renderDropDown($name,$rows,$selected=null,$id=null,$multiple=null,$size=null,$javascript=null,$prefix='@[{(&*_'){
        $multiple   = ($multiple)?' multiple = "multiple"':'';
        $size       = ($size)?$size:'1';
        $id     = ($id)?' id = "'.$id.'"':'';
        $html   = '<select name="'.$name.'"'.$id.$multiple.' size="'.$size.'"'.$javascript.' >';
        $str    = '';

        foreach($rows as $row){

            if($multiple){
                if($selected){
                    if(count($selected)>0){
                        foreach($selected as $item){
                            if(($item -> fieldsid) == ($row -> fieldsid) && ($item -> value)==($row -> name)){
                                $_selected   = ' selected="selected"';
                                break;
                            }
                            else
                                $_selected  = '';
                        }
                    }
                }
                else
                    $_selected   = '';
            }
            else{
                if($selected){
                    if(($row -> name == $selected[0] -> value) && ($row -> fieldsid == $selected[0] -> fieldsid))
                        $_selected  = ' selected="selected"';
                    else
                        $_selected  = '';
                }
                else
                    $_selected  = '';
            }
                $str .= '<option value="'.$row -> name.$prefix.$row -> value.'"'
                        .($_selected).'>'
                        .$row -> name.'</option>';
        }
        $html   .= $str
                  .'</select>';
        return $html;
    }

    // extra radio button
    public function renderRadio($name,$rows,$checked=null,$id=null,$javascript=null
            ,$image=false,$imageWidth=null,$imageHeight = null,$imageCrop = null,$prefix='@[{(&*_'){
        $html   = '<table>';
        $str    = '';
        $id     = ($id)?' id="'.$id.'"':'';

        if(count($rows)>0){
            foreach($rows as $row){
                if(!empty($row -> name) or !empty($row -> value)){}
                if($checked){
                    if(count($checked)>0){
                        foreach($checked as $item){
                            if(($item -> fieldsid) == ($row -> fieldsid) && ($item -> value) == ($row -> name)){

                                $_checked   = ' checked="checked"';
                                break;

                            }
                            else
                                $_checked   = '';
                        }
                    }
                }
                else
                    $_checked   = '';

                $str      .= '<tr>';
                $str      .= '<td>';
                if($image != false){
                    if(isset($row -> image) && !empty($row -> image)){
                        $str    .= ArticleHTML::renderImg(JURI::root().$row -> image,$imageWidth,$imageHeight,$imageCrop);
                    }
                }
                $str      .= '</td>';
                $str      .= '<td>';
                $str      .='<input type="radio" name="'.$name.'"'
                    .' value="'.$row -> name.$prefix.$row -> value.'"'
                    .$id
                    .$_checked
                    .$javascript.'/>';

                $str      .= '&nbsp;'.$row -> name.'</td>';
                $str      .= '</tr>';
            }
        }
        $html   .= $str.'</table>';

        return $html;
    }

    // extra radio button
    public function renderCheckBox($name,$rows,$id=null,$checked=null,$javascript=null
            ,$image=false,$imageWidth=null,$imageHeight = null,$imageCrop = null,$prefix='@[{(&*_'){
        $html   = '<table cellspacing="0" cellpadding="0">';
        $str    = '';
        $id     = ($id)?' id="'.$id.'"':'';

        if(count($rows)>0){

            foreach($rows as $row){
                if($checked){
                    if(count($checked)>0){
                        foreach($checked as $item){
                            if(($item -> fieldsid) == ($row -> fieldsid) && ($item -> value) == ($row -> name)){

                                $_checked   = ' checked="checked"';
                                break;

                            }
                            else
                                $_checked   = '';
                        }
                    }
                }
                else
                    $_checked   = '';

//                $checked    = ($checked==$row -> value)?' checked = "'.$checked.'"':'';

                $str      .= '<tr>';
                $str      .= '<td>';
                if($image != false){
                    if(isset($row -> image) && !empty($row -> image)){
                        $str    .= ArticleHTML::renderImg(JURI::root().$row -> image,$imageWidth,$imageHeight,$imageCrop);
                    }
                }
                $str      .= '</td>';
                $str      .= '<td>';
                $str      .= '<input type="checkbox" name="'.$name.'"'
                    .' value="'.$row -> name.$prefix.$row -> value.'"'
                    .$id
                    .$_checked.$javascript.'/>';
                $str      .= '&nbsp;'.$row -> name.'</td>';
                $str      .= '</tr>';
            }
        }
        $html   .= $str.'</table>';

        return $html;
    }

    // extra link
    public  function renderLink($name,$text=null,$url=null,$target=null){
        $target = ($target)?$target:'_self';
        $url    = (isset($url) && $url!=null)?$url:'';

        $html   = '<table>';
        $html   .= '<tr>'
            .'<td>'.JText::_('Text').'</td>'
            .'<td><input type="text" name="'.$name.'" value="'.$text.'"></td>'
            .'</tr>';
        $html   .='<tr>'
            .'<td>'.JText::_('URL').'</td>'
            .'<td><input type="text" name="'.$name.'" value="http://'.$url.'"></td>'
            .'</tr>';
        $html   .='<tr>'
            .'<td>'.JText::_('Open in').'</td>'
            .'<td><select name="'.$name.'">'
            .'<option value="_self" '.(($target=='_self')?' selected="selected"':'').'>'
            .JText::_('Same window')
            .'</option>'
            .'<option value="_blank" '.(($target=='_blank')?' selected="selected"':'').'>'
            .JText::_('New window')
            .'</option>'
            .'</select></td>'
            .'</tr>';
        $html   .= '</table>';

        return $html;
    }
//    public function renderLink($text=null,$link,$target=null,$title=null,$id=null,$javascript=null){
//        $target = ($target)?$target:'_self';
//        $id     = ($id)?' id = '.$id:'';
//        $html   = '<a href="'.$link.'" title="'.$title.'"'.$id.'target="'.$target.'"'.$javascript.'>'.$text.'</a>';
//        return $html;
//    }

    // extra file
    public function renderFile($name,$size=null,$javascript=null){
        $size   = ($size)?$size:'40';
        $html   = '<input type="file" name="'.$name.'" size="'.$size.'"'.$javascript.'/>';
        return $html;
    }

    public function  renderDate(){
        //$html   = JHtml::calendar( '2012-04-07 23:26:28', 'tz_test', 'tz_test');
        //$html   = JHtml::_('behavior.calendar');
//        //return JHtml::calendar( '2012-04-07 23:26:28', 'tz_test', 'tz_test');
        //return $html;

    }


}
?>