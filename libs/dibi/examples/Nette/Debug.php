<?php
/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt, and/or GPL license.
 *
 * For more information please see http://nette.org
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nette.org/license  Nette license
 * @link       http://nette.org
 * @category   Nette
 * @package    Nette
 */

final
class
Debug{public
static$productionMode;public
static$consoleMode;public
static$time;private
static$firebugDetected;private
static$ajaxDetected;public
static$maxDepth=3;public
static$maxLen=150;public
static$showLocation=FALSE;const
DEVELOPMENT=FALSE;const
PRODUCTION=TRUE;const
DETECT=NULL;public
static$strictMode=FALSE;public
static$onFatalError=array();public
static$mailer=array(__CLASS__,'defaultMailer');public
static$emailSnooze=172800;private
static$enabled=FALSE;private
static$logFile;private
static$logHandle;private
static$sendEmails;private
static$emailHeaders=array('To'=>'','From'=>'noreply@%host%','X-Mailer'=>'Nette Framework','Subject'=>'PHP: An error occurred on the server %host%','Body'=>'[%date%] %message%');public
static$showBar=TRUE;private
static$panels=array();private
static$dumps;private
static$errors;public
static$counters=array();const
LOG='LOG';const
INFO='INFO';const
WARN='WARN';const
ERROR='ERROR';const
TRACE='TRACE';const
EXCEPTION='EXCEPTION';const
GROUP_START='GROUP_START';const
GROUP_END='GROUP_END';final
public
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}public
static
function
_init(){self::$time=microtime(TRUE);self::$consoleMode=PHP_SAPI==='cli';self::$productionMode=self::DETECT;self::$firebugDetected=isset($_SERVER['HTTP_USER_AGENT'])&&strpos($_SERVER['HTTP_USER_AGENT'],'FirePHP/');self::$ajaxDetected=isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&$_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';$tab=array(__CLASS__,'renderTab');$panel=array(__CLASS__,'renderPanel');self::addPanel(new
DebugPanel('time',$tab,$panel));self::addPanel(new
DebugPanel('memory',$tab,$panel));self::addPanel(new
DebugPanel('errors',$tab,$panel));self::addPanel(new
DebugPanel('dumps',$tab,$panel));}public
static
function
dump($var,$return=FALSE){if(!$return&&self::$productionMode){return$var;}$output="<pre class=\"nette-dump\">".self::_dump($var,0)."</pre>\n";if(!$return&&self::$showLocation){$trace=debug_backtrace();$i=isset($trace[1]['class'])&&$trace[1]['class']===__CLASS__?1:0;if(isset($trace[$i]['file'],$trace[$i]['line'])){$output=substr_replace($output,' <small>'.htmlspecialchars("in file {$trace[$i]['file']} on line {$trace[$i]['line']}",ENT_NOQUOTES).'</small>',-8,0);}}if(self::$consoleMode){$output=htmlspecialchars_decode(strip_tags($output),ENT_NOQUOTES);}if($return){return$output;}else{echo$output;return$var;}}public
static
function
barDump($var,$title=NULL){if(!self::$productionMode){$dump=array();foreach((is_array($var)?$var:array(''=>$var))as$key=>$val){$dump[$key]=self::dump($val,TRUE);}self::$dumps[]=array('title'=>$title,'dump'=>$dump);}return$var;}private
static
function
_dump(&$var,$level){static$tableUtf,$tableBin,$re='#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';if($tableUtf===NULL){foreach(range("\x00","\xFF")as$ch){if(ord($ch)<32&&strpos("\r\n\t",$ch)===FALSE)$tableUtf[$ch]=$tableBin[$ch]='\\x'.str_pad(dechex(ord($ch)),2,'0',STR_PAD_LEFT);elseif(ord($ch)<127)$tableUtf[$ch]=$tableBin[$ch]=$ch;else{$tableUtf[$ch]=$ch;$tableBin[$ch]='\\x'.dechex(ord($ch));}}$tableUtf['\\x']=$tableBin['\\x']='\\\\x';}if(is_bool($var)){return"<span>bool</span>(".($var?'TRUE':'FALSE').")\n";}elseif($var===NULL){return"<span>NULL</span>\n";}elseif(is_int($var)){return"<span>int</span>($var)\n";}elseif(is_float($var)){return"<span>float</span>($var)\n";}elseif(is_string($var)){if(self::$maxLen&&strlen($var)>self::$maxLen){$s=htmlSpecialChars(substr($var,0,self::$maxLen),ENT_NOQUOTES).' ... ';}else{$s=htmlSpecialChars($var,ENT_NOQUOTES);}$s=strtr($s,preg_match($re,$s)||preg_last_error()?$tableBin:$tableUtf);return"<span>string</span>(".strlen($var).") \"$s\"\n";}elseif(is_array($var)){$s="<span>array</span>(".count($var).") ";$space=str_repeat($space1='   ',$level);static$marker;if($marker===NULL)$marker=uniqid("\x00",TRUE);if(empty($var)){}elseif(isset($var[$marker])){$s.="{\n$space$space1*RECURSION*\n$space}";}elseif($level<self::$maxDepth||!self::$maxDepth){$s.="<code>{\n";$var[$marker]=0;foreach($var
as$k=>&$v){if($k===$marker)continue;$k=is_int($k)?$k:'"'.strtr($k,preg_match($re,$k)||preg_last_error()?$tableBin:$tableUtf).'"';$s.="$space$space1$k => ".self::_dump($v,$level+1);}unset($var[$marker]);$s.="$space}</code>";}else{$s.="{\n$space$space1...\n$space}";}return$s."\n";}elseif(is_object($var)){$arr=(array)$var;$s="<span>object</span>(".get_class($var).") (".count($arr).") ";$space=str_repeat($space1='   ',$level);static$list=array();if(empty($arr)){$s.="{}";}elseif(in_array($var,$list,TRUE)){$s.="{\n$space$space1*RECURSION*\n$space}";}elseif($level<self::$maxDepth||!self::$maxDepth){$s.="<code>{\n";$list[]=$var;foreach($arr
as$k=>&$v){$m='';if($k[0]==="\x00"){$m=$k[1]==='*'?' <span>protected</span>':' <span>private</span>';$k=substr($k,strrpos($k,"\x00")+1);}$k=strtr($k,preg_match($re,$k)||preg_last_error()?$tableBin:$tableUtf);$s.="$space$space1\"$k\"$m => ".self::_dump($v,$level+1);}array_pop($list);$s.="$space}</code>";}else{$s.="{\n$space$space1...\n$space}";}return$s."\n";}elseif(is_resource($var)){return"<span>resource of type</span>(".get_resource_type($var).")\n";}else{return"<span>unknown type</span>\n";}}public
static
function
timer($name=NULL){static$time=array();$now=microtime(TRUE);$delta=isset($time[$name])?$now-$time[$name]:0;$time[$name]=$now;return$delta;}public
static
function
enable($mode=NULL,$logFile=NULL,$email=NULL){error_reporting(E_ALL|E_STRICT);if(is_bool($mode)){self::$productionMode=$mode;}elseif(is_string($mode)){$mode=preg_split('#[,\s]+#',$mode);}if(is_array($mode)){self::$productionMode=!isset($_SERVER['REMOTE_ADDR'])||!in_array($_SERVER['REMOTE_ADDR'],$mode,TRUE);}if(self::$productionMode===self::DETECT){if(class_exists('Environment')){self::$productionMode=Environment::isProduction();}elseif(isset($_SERVER['SERVER_ADDR'])||isset($_SERVER['LOCAL_ADDR'])){$addr=isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['LOCAL_ADDR'];$oct=explode('.',$addr);self::$productionMode=$addr!=='::1'&&(count($oct)!==4||($oct[0]!=='10'&&$oct[0]!=='127'&&($oct[0]!=='172'||$oct[1]<16||$oct[1]>31)&&($oct[0]!=='169'||$oct[1]!=='254')&&($oct[0]!=='192'||$oct[1]!=='168')));}else{self::$productionMode=!self::$consoleMode;}}if(self::$productionMode&&$logFile!==FALSE){self::$logFile='log/php_error.log';if(class_exists('Environment')){if(is_string($logFile)){self::$logFile=Environment::expand($logFile);}else
try{self::$logFile=Environment::expand('%logDir%/php_error.log');}catch(InvalidStateException$e){}}elseif(is_string($logFile)){self::$logFile=$logFile;}ini_set('error_log',self::$logFile);}if(function_exists('ini_set')){ini_set('display_errors',!self::$productionMode);ini_set('html_errors',!self::$logFile&&!self::$consoleMode);ini_set('log_errors',FALSE);}elseif(ini_get('display_errors')!=!self::$productionMode&&ini_get('display_errors')!==(self::$productionMode?'stderr':'stdout')){throw
new
NotSupportedException('Function ini_set() must be enabled.');}self::$sendEmails=self::$logFile&&$email;if(self::$sendEmails){if(is_string($email)){self::$emailHeaders['To']=$email;}elseif(is_array($email)){self::$emailHeaders=$email+self::$emailHeaders;}}if(!defined('E_DEPRECATED')){define('E_DEPRECATED',8192);}if(!defined('E_USER_DEPRECATED')){define('E_USER_DEPRECATED',16384);}register_shutdown_function(array(__CLASS__,'_shutdownHandler'));set_exception_handler(array(__CLASS__,'_exceptionHandler'));set_error_handler(array(__CLASS__,'_errorHandler'));self::$enabled=TRUE;}public
static
function
isEnabled(){return
self::$enabled;}public
static
function
log($message){error_log(@date('[Y-m-d H-i-s] ').trim($message).PHP_EOL,3,self::$logFile);}public
static
function
_shutdownHandler(){static$types=array(E_ERROR=>1,E_CORE_ERROR=>1,E_COMPILE_ERROR=>1,E_PARSE=>1);$error=error_get_last();if(isset($types[$error['type']])){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}if(ini_get('html_errors')){$error['message']=html_entity_decode(strip_tags($error['message']),ENT_QUOTES,'UTF-8');}self::processException(new
FatalErrorException($error['message'],0,$error['type'],$error['file'],$error['line'],NULL),TRUE);}if(self::$showBar&&!self::$productionMode&&!self::$ajaxDetected&&!self::$consoleMode){foreach(headers_list()as$header){if(strncasecmp($header,'Content-Type:',13)===0){if(substr($header,14,9)==='text/html'){break;}return;}}$panels=array();foreach(self::$panels
as$panel){$panels[]=array('id'=>preg_replace('#[^a-z0-9]+#i','-',$panel->getId()),'tab'=>$tab=(string)$panel->getTab(),'panel'=>$tab?(string)$panel->getPanel():NULL);}ob_start();?>

<!-- Nette Debug Bar -->

<style id="nette-debug-style">#nette-debug{display:none}body#nette-debug{margin:5px 5px 0;display:block}#nette-debug *{font:inherit;color:inherit;background:transparent;margin:0;padding:0;border:none;text-align:inherit;list-style:inherit}#nette-debug .nette-fixed-coords{position:fixed;_position:absolute;right:0;bottom:0}#nette-debug a{color:#125eae;text-decoration:none}#nette-debug .nette-panel a{color:#125eae;text-decoration:none}#nette-debug a:hover,#nette-debug a:active,#nette-debug a:focus{background-color:#125eae;color:white}#nette-debug .nette-panel h2,#nette-debug .nette-panel h3,#nette-debug .nette-panel p{margin:.4em 0}#nette-debug .nette-panel table{border-collapse:collapse;background:#fcfae5}#nette-debug .nette-panel .nette-alt td{background:#f5f2db}#nette-debug .nette-panel td,#nette-debug .nette-panel th{border:1px solid #dcd7c8;padding:2px 5px;vertical-align:top;text-align:left}#nette-debug .nette-panel th{background:#f0eee6;color:#655e5e;font-size:90%;font-weight:bold}#nette-debug .nette-panel pre,#nette-debug .nette-panel code{font:9pt/1.5 Consolas,monospace}.nette-hidden{display:none}#nette-debug-bar{font:normal normal 12px/21px Tahoma,sans-serif;color:#333;background:#edeae0;position:relative;top:-5px;left:-5px;height:21px;_float:left;min-width:50px;white-space:nowrap;z-index:23181;opacity:.9}#nette-debug-bar:hover{opacity:1}#nette-debug-bar ul{list-style:none none}#nette-debug-bar li{float:left}#nette-debug-bar img{vertical-align:middle;position:relative;top:-1px;margin-right:3px}#nette-debug-bar li a{color:#000;display:block;padding:0 4px}#nette-debug-bar li a:hover{color:black;background:#c3c1b8}#nette-debug-bar li .nette-warning{color:#d32b2b;font-weight:bold}#nette-debug-bar li div{padding:0 4px}#nette-debug-logo{background:url('data:image/png;base64,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') 0 50% no-repeat;min-width:45px;cursor:move}#nette-debug-logo span{display:none}#nette-debug-bar-bgl,#nette-debug-bar-bgx,#nette-debug-bar-bgr{position:absolute;z-index:-1;top:-7px;height:37px}#nette-debug-bar-bgl{background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAlCAMAAABBCPgrAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAALdQTFRFPj4+AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAISEhAAAAIyMjAAAAHR0dISEhNzc3Nzc3NjY2NTU1PT09PT09PDw8Pj4+SkpJS0tKy8nDxcO7zMrEx8S97evj4+HY5uPb7uzk5+Ta6OXb6Obc6ebd6ufe6+jf7Onf7erg7uvh7+zi8O3j8O3k8e7k8e7l8e/l8u/m8u/n8vDn8vDo8/Do8/Ho8/HpTk+bYwAAACd0Uk5TAAECAwQFBgcICQoLDA0ODxAQERMVLi8wM0hJSk9TU6qsrLDs7vHxx6CxnwAAAKJJREFUGBkFwUGKVEEARMF49WuEQdy5Ee9/R6HpTiMCCIEQISRFpNSFUlVSVacuqO8f93Gp+vmr+8eN6uv5i6N07hccVOc3HKr4jAtgc0UZuUjGXBKDCxhcwIYLGHMBM7uAjV3AZrvM+JhdzMy2a9lstgN8/m3bYdveL9ueSj338zl7orx7v1+vVJ1zzjnnEcLskcJsD2Ha9hAwHgQzgRABhP+DAUz8vYhDMAAAAABJRU5ErkJggg==') no-repeat;left:-12px;width:12px}#nette-debug-bar-bgx{background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAlCAYAAACDKIOpAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAF5JREFUeNpUjjEOgDAMA932DXynA/8X6sAnGBErjYlLW8Fy8jlRFAAI0RH/SC9yziuu86AUDjroiR+ldA6a0hDNHLUqSWmj6yvo2i6rS1vZi2xRd0/UNL4ypSDgEWAAvWU1L9Te5UYAAAAASUVORK5CYII=') repeat-x;left:0;right:5px}#nette-debug-bar-bgr{background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAAlCAYAAACZFGMnAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAb5JREFUeNq8Vb1OwzAQtlNTIoRYYIhEFfUVGLp2YykDA48FG8/AUvEKqFIGQDwEA20CKv9UUFWkPuzUTi/pOW0XTjolvfrL951/PnPGGGelaLfbwIiIoih7aoBX+g9cH7AgHcJkzaRnkuNU4BygC3XEAI73ggqB5EFpsFOylYVB0vEBMMqgprR2wjDcD4JAy/wZjUayiiWLz7cBPD/dv9xe97pHnc5Jo9HYU+Utlb7pV6AJ4jno41VnH+5uepetVutAlbcNcFPlBuo9m0kPYC692QwPfd8P0AAPLb6d/uwLTAN1CiF2OOd1MxhQ8xz3JixgxlhYPypn6ySlzAEI55mpJ8MwsWVMuA6KybKQG5uXnohpcf04AZj3BCCrmKp6Wh2Qz966IdZlMSD2P0yeA0Qdd8Ant2r2KJ9YykQd+ZmpWGCapl9qCX6RT9A94R8P/fhqPB4PCSZY6EkxvA/ix+j07PwiSZLYMLlcKXPOYy1pMpkM4zhWmORb1acGNEW2lkvWO3cXFaQjCzK1vJQwSnIw7ild0WELtjwldoGsugwEMpBVbo2C68hSPwvy8OUmCKuCZVepoOhdd66NPwEGACivHvMuwfIKAAAAAElFTkSuQmCC') no-repeat;right:-8px;width:13px}#nette-debug .nette-panel{font:normal normal 12px/1.5 sans-serif;background:white;color:#333}#nette-debug h1{font:normal normal 23px/1.4 Tahoma,sans-serif;color:#575753;background:#edeae0;margin:-5px -5px 5px;padding:0 25px 5px 5px}#nette-debug .nette-mode-peek .nette-inner,#nette-debug .nette-mode-float .nette-inner{max-width:700px;max-height:500px;overflow:auto}#nette-debug .nette-panel .nette-icons{display:none}#nette-debug .nette-mode-peek{display:none;position:relative;z-index:23180;padding:5px;min-width:150px;min-height:50px;border:5px solid #edeae0;border-radius:5px;-moz-border-radius:5px}#nette-debug .nette-mode-peek h1{cursor:move}#nette-debug .nette-mode-float{position:relative;z-index:23179;padding:5px;min-width:150px;min-height:50px;border:5px solid #edeae0;border-radius:5px;-moz-border-radius:5px;opacity:.9;-moz-box-shadow:1px 1px 6px #666;-webkit-box-shadow:1px 1px 6px #666;box-shadow:1px 1px 6px #666}#nette-debug .nette-focused{z-index:23180;opacity:1}#nette-debug .nette-mode-float h1{cursor:move}#nette-debug .nette-mode-float .nette-icons{display:block;position:absolute;top:0;right:0;font-size:18px}#nette-debug .nette-icons a{color:#575753}#nette-debug .nette-icons a:hover{color:white}</style>

<!--[if lt IE 8]><style>#nette-debug-bar img{display:none}#nette-debug-bar li{border-left:1px solid #DCD7C8;padding:0 3px}#nette-debug-logo span{background:#edeae0;display:inline}</style><![endif]-->


<script id="nette-debug-script">/*<![CDATA[*//*
    http://nette.org/license  Nette license
*/
var Nette=Nette||{};
(function(){Nette.Class=function(a){var b=a.constructor||function(){},c;delete a.constructor;if(a.Extends){var d=function(){this.constructor=b};d.prototype=a.Extends.prototype;b.prototype=new d;delete a.Extends}if(a.Static){for(c in a.Static)b[c]=a.Static[c];delete a.Static}for(c in a)b.prototype[c]=a[c];return b};Nette.Q=Nette.Class({Static:{factory:function(a){return new Nette.Q(a)},implement:function(a){var b,c=Nette.Q.implement,d=Nette.Q.prototype;for(b in a){c[b]=a[b];d[b]=function(f){return function(){return this.each(c[f],
arguments)}}(b)}}},constructor:function(a){if(typeof a==="string")a=this._find(document,a);else if(!a||a.nodeType||a.length===void 0||a===window)a=[a];for(var b=0,c=a.length;b<c;b++)if(a[b])this[this.length++]=a[b]},length:0,find:function(a){return new Nette.Q(this._find(this[0],a))},_find:function(a,b){if(!a||!b)return[];else if(document.querySelectorAll)return a.querySelectorAll(b);else if(b.charAt(0)==="#")return[document.getElementById(b.substring(1))];else{b=b.split(".");a=a.getElementsByTagName(b[0]||
"*");if(b[1]){var c=[];b=new RegExp("(^|\\s)"+b[1]+"(\\s|$)");for(var d=0,f=a.length;d<f;d++)b.test(a[d].className)&&c.push(a[d]);return c}else return a}},dom:function(){return this[0]},each:function(a,b){for(var c=0,d;c<this.length;c++)if((d=a.apply(this[c],b||[]))!==void 0)return d;return this}});var h=Nette.Q.factory,e=Nette.Q.implement;e({bind:function(a,b){if(document.addEventListener&&(a==="mouseenter"||a==="mouseleave")){var c=b;a=a==="mouseenter"?"mouseover":"mouseout";b=function(g){for(var i=
g.relatedTarget;i;i=i.parentNode)if(i===this)return;c.call(this,g)}}var d=e.data.call(this);d=d.events=d.events||{};if(!d[a]){var f=this,j=d[a]=[],k=e.bind.genericHandler=function(g){if(!g.target)g.target=g.srcElement;if(!g.preventDefault)g.preventDefault=function(){g.returnValue=false};if(!g.stopPropagation)g.stopPropagation=function(){g.cancelBubble=true};g.stopImmediatePropagation=function(){this.stopPropagation();i=j.length};for(var i=0;i<j.length;i++)j[i].call(f,g)};if(document.addEventListener)this.addEventListener(a,
k,false);else document.attachEvent&&this.attachEvent("on"+a,k)}d[a].push(b)},addClass:function(a){this.className=this.className.replace(/^|\s+|$/g," ").replace(" "+a+" "," ")+" "+a},removeClass:function(a){this.className=this.className.replace(/^|\s+|$/g," ").replace(" "+a+" "," ")},hasClass:function(a){return this.className.replace(/^|\s+|$/g," ").indexOf(" "+a+" ")>-1},show:function(){var a=e.show.display=e.show.display||{},b=this.tagName;if(!a[b]){var c=document.body.appendChild(document.createElement(b));
a[b]=e.css.call(c,"display")}this.style.display=a[b]},hide:function(){this.style.display="none"},css:function(a){return this.currentStyle?this.currentStyle[a]:window.getComputedStyle?document.defaultView.getComputedStyle(this,null).getPropertyValue(a):void 0},data:function(){return this.nette=this.nette||{}},_trav:function(a,b,c){for(b=b.split(".");a&&!(a.nodeType===1&&(!b[0]||a.tagName.toLowerCase()===b[0])&&(!b[1]||e.hasClass.call(a,b[1])));)a=a[c];return h(a)},closest:function(a){return e._trav(this,
a,"parentNode")},prev:function(a){return e._trav(this.previousSibling,a,"previousSibling")},next:function(a){return e._trav(this.nextSibling,a,"nextSibling")},offset:function(a){for(var b=this,c=a?{left:-a.left||0,top:-a.top||0}:e.position.call(b);b=b.offsetParent;){c.left+=b.offsetLeft;c.top+=b.offsetTop}if(a)e.position.call(this,{left:-c.left,top:-c.top});else return c},position:function(a){if(a){this.nette&&this.nette.onmove&&this.nette.onmove.call(this,a);this.style.left=(a.left||0)+"px";this.style.top=
(a.top||0)+"px"}else return{left:this.offsetLeft,top:this.offsetTop,width:this.offsetWidth,height:this.offsetHeight}},draggable:function(a){var b=h(this),c=document.documentElement,d;a=a||{};h(a.handle||this).bind("mousedown",function(f){f.preventDefault();f.stopPropagation();if(e.draggable.binded)return c.onmouseup(f);var j=b[0].offsetLeft-f.clientX,k=b[0].offsetTop-f.clientY;e.draggable.binded=true;d=false;c.onmousemove=function(g){g=g||event;if(!d){a.draggedClass&&b.addClass(a.draggedClass);a.start&&
a.start(g,b);d=true}b.position({left:g.clientX+j,top:g.clientY+k});return false};c.onmouseup=function(g){if(d){a.draggedClass&&b.removeClass(a.draggedClass);a.stop&&a.stop(g||event,b)}e.draggable.binded=c.onmousemove=c.onmouseup=null;return false}}).bind("click",function(f){if(d){f.stopImmediatePropagation();preventClick=false}})}})})();
(function(){Nette.Debug={};var h=Nette.Q.factory,e=Nette.Debug.Panel=Nette.Class({Extends:Nette.Q,Static:{PEEK:"nette-mode-peek",FLOAT:"nette-mode-float",WINDOW:"nette-mode-window",FOCUSED:"nette-focused",factory:function(a){return new e(a)},_toggle:function(a){var b=a.rel;b=b.charAt(0)==="#"?h(b):h(a)[b.charAt(0)==="<"?"prev":"next"](b.substring(1));if(b.css("display")==="none"){b.show();a.innerHTML=a.innerHTML.replace("\u25ba","\u25bc")}else{b.hide();a.innerHTML=a.innerHTML.replace("\u25bc","\u25ba")}}},
constructor:function(a){Nette.Q.call(this,"#nette-debug-panel-"+a.replace("nette-debug-panel-",""))},reposition:function(){if(this.hasClass(e.WINDOW))window.resizeBy(document.documentElement.scrollWidth-document.documentElement.clientWidth,document.documentElement.scrollHeight-document.documentElement.clientHeight);else{this.position(this.position());if(this.position().width)document.cookie=this.dom().id+"="+this.position().left+":"+this.position().top+"; path=/"}},focus:function(){if(this.hasClass(e.WINDOW))this.data().win.focus();
else{clearTimeout(this.data().blurTimeout);this.addClass(e.FOCUSED).show()}},blur:function(){this.removeClass(e.FOCUSED);if(this.hasClass(e.PEEK)){var a=this;this.data().blurTimeout=setTimeout(function(){a.hide()},50)}},toFloat:function(){this.removeClass(e.WINDOW).removeClass(e.PEEK).addClass(e.FLOAT).show().reposition();return this},toPeek:function(){this.removeClass(e.WINDOW).removeClass(e.FLOAT).addClass(e.PEEK).hide();document.cookie=this.dom().id+"=; path=/"},toWindow:function(){var a=this,
b,c;c=this.offset();var d=this.dom().id;c.left+=typeof window.screenLeft==="number"?window.screenLeft:window.screenX+10;c.top+=typeof window.screenTop==="number"?window.screenTop:window.screenY+50;if(b=window.open("",d.replace(/-/g,"_"),"left="+c.left+",top="+c.top+",width="+c.width+",height="+(c.height+15)+",resizable=yes,scrollbars=yes")){c=b.document;c.write('<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>'+h("#nette-debug-style").dom().innerHTML+"</style><script>"+
h("#nette-debug-script").dom().innerHTML+'<\/script><body id="nette-debug">');c.body.innerHTML='<div class="nette-panel nette-mode-window" id="'+d+'">'+this.dom().innerHTML+"</div>";b.Nette.Debug.Panel.factory(d).initToggler().reposition();c.title=a.find("h1").dom().innerHTML;h([b]).bind("unload",function(){a.toPeek();b.close()});h(c).bind("keyup",function(f){f.keyCode===27&&b.close()});document.cookie=d+"=window; path=/";this.hide().removeClass(e.FLOAT).removeClass(e.PEEK).addClass(e.WINDOW).data().win=
b}},init:function(){var a=this,b;a.data().onmove=function(c){var d=document,f=window.innerWidth||d.documentElement.clientWidth||d.body.clientWidth;d=window.innerHeight||d.documentElement.clientHeight||d.body.clientHeight;c.left=Math.max(Math.min(c.left,0.8*this.offsetWidth),0.2*this.offsetWidth-f);c.top=Math.max(Math.min(c.top,0.8*this.offsetHeight),this.offsetHeight-d)};h(window).bind("resize",function(){a.reposition()});a.draggable({handle:a.find("h1"),stop:function(){a.toFloat()}}).bind("mouseenter",
function(){a.focus()}).bind("mouseleave",function(){a.blur()});this.initToggler();a.find(".nette-icons").find("a").bind("click",function(c){this.rel==="close"?a.toPeek():a.toWindow();c.preventDefault()});if(b=document.cookie.match(new RegExp(a.dom().id+"=(window|(-?[0-9]+):(-?[0-9]+))")))b[2]?a.toFloat().position({left:b[2],top:b[3]}):a.toWindow();else a.addClass(e.PEEK)},initToggler:function(){var a=this;this.bind("click",function(b){var c=h(b.target).closest("a"),d=c.dom();if(d&&c.hasClass("nette-toggler")){e._toggle(d);
b.preventDefault();a.reposition()}});return this}});Nette.Debug.Bar=Nette.Class({Extends:Nette.Q,constructor:function(){Nette.Q.call(this,"#nette-debug-bar")},init:function(){var a=this,b;a.data().onmove=function(c){var d=document,f=window.innerWidth||d.documentElement.clientWidth||d.body.clientWidth;d=window.innerHeight||d.documentElement.clientHeight||d.body.clientHeight;c.left=Math.max(Math.min(c.left,0),this.offsetWidth-f);c.top=Math.max(Math.min(c.top,0),this.offsetHeight-d)};h(window).bind("resize",
function(){a.position(a.position())});a.draggable({draggedClass:"nette-dragged",stop:function(){document.cookie=a.dom().id+"="+a.position().left+":"+a.position().top+"; path=/"}});a.find("a").bind("click",function(c){if(this.rel==="close")h("#nette-debug").hide();else if(this.rel){var d=e.factory(this.rel);if(c.shiftKey)d.toFloat().toWindow();else if(d.hasClass(e.FLOAT)){var f=h(this).offset();d.offset({left:f.left-d.position().width+f.width+4,top:f.top-d.position().height-4}).toPeek()}else d.toFloat().position({left:d.position().left-
Math.round(Math.random()*100)-20,top:d.position().top-Math.round(Math.random()*100)-20}).reposition()}c.preventDefault()}).bind("mouseenter",function(){if(!(!this.rel||this.rel==="close"||a.hasClass("nette-dragged"))){var c=e.factory(this.rel);c.focus();if(c.hasClass(e.PEEK)){var d=h(this).offset();c.offset({left:d.left-c.position().width+d.width+4,top:d.top-c.position().height-4})}}}).bind("mouseleave",function(){!this.rel||this.rel==="close"||a.hasClass("nette-dragged")||e.factory(this.rel).blur()});
if(b=document.cookie.match(new RegExp(a.dom().id+"=(-?[0-9]+):(-?[0-9]+)")))a.position({left:b[1],top:b[2]});a.find("a").each(function(){!this.rel||this.rel==="close"||e.factory(this.rel).init()})}})})();/*]]>*/</script>



<div id="nette-debug">

<?php foreach($panels
as$id=>$panel):?>
<div class="nette-fixed-coords">
	<div class="nette-panel" id="nette-debug-panel-<?php echo$panel['id']?>">
		<div id="nette-debug-<?php echo$panel['id']?>"><?php echo$panel['panel']?></div>
		<div class="nette-icons">
			<a href="#" title="open in window">&curren;</a>
			<a href="#" rel="close" title="close window">&times;</a>
		</div>
	</div>
</div>
<?php endforeach?>

<div class="nette-fixed-coords">
	<div id="nette-debug-bar">
		<ul>
			<li id="nette-debug-logo">&nbsp;<span>Nette Framework</span></li>
			<?php foreach($panels
as$panel):if(!$panel['tab'])continue;?>
			<li><?php if($panel['panel']):?><a href="#" rel="<?php echo$panel['id']?>"><?php echo
trim($panel['tab'])?></a><?php else:echo'<div>',trim($panel['tab']),'</div>';endif?></li>
			<?php endforeach?>
			<li><a href="#" rel="close" title="close debug bar">&times;</a></li>
		</ul>

		<div id="nette-debug-bar-bgl"></div><div id="nette-debug-bar-bgx"></div><div id="nette-debug-bar-bgr"></div>
	</div>
</div>

</div>


<script>(function(){(new Nette.Debug.Bar).init();document.body.appendChild(Nette.Q.factory("#nette-debug").show().dom())})();</script>

<!-- /Nette Debug Bar -->
<?php
echo
preg_replace_callback('#(</textarea|</pre|</script|^).*?(?=<textarea|<pre|<script|$)#si',create_function('$m','return trim(preg_replace("#[ \t\r\n]+#", " ", $m[0]));'),ob_get_clean());}}public
static
function
_exceptionHandler(Exception$exception){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}self::processException($exception,TRUE);exit;}public
static
function
_errorHandler($severity,$message,$file,$line,$context){if($severity===E_RECOVERABLE_ERROR||$severity===E_USER_ERROR){throw
new
FatalErrorException($message,0,$severity,$file,$line,$context);}elseif(($severity&error_reporting())!==$severity){return
NULL;}elseif(self::$strictMode){self::_exceptionHandler(new
FatalErrorException($message,0,$severity,$file,$line,$context),TRUE);}static$types=array(E_WARNING=>'Warning',E_USER_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'Notice',E_STRICT=>'Strict standards',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'Deprecated');$message='PHP '.(isset($types[$severity])?$types[$severity]:'Unknown error').": $message in $file:$line";if(self::$logFile){if(self::$sendEmails){self::sendEmail($message);}self::log($message);return
NULL;}elseif(!self::$productionMode){if(self::$showBar){self::$errors[]=$message;}if(self::$firebugDetected&&!headers_sent()){self::fireLog(strip_tags($message),self::ERROR);}return
self::$consoleMode||(!self::$showBar&&!self::$ajaxDetected)?FALSE:NULL;}return
FALSE;}public
static
function
processException(Exception$exception,$outputAllowed=FALSE){if(!self::$enabled){return;}elseif(self::$logFile){try{$hash=md5($exception.(method_exists($exception,'getPrevious')?$exception->getPrevious():(isset($exception->previous)?$exception->previous:'')));self::log("PHP Fatal error: Uncaught ".str_replace("Stack trace:\n".$exception->getTraceAsString(),'',$exception));foreach(new
DirectoryIterator(dirname(self::$logFile))as$entry){if(strpos($entry,$hash)){$skip=TRUE;break;}}$file='compress.zlib://'.dirname(self::$logFile)."/exception ".@date('Y-m-d H-i-s')." $hash.html.gz";if(empty($skip)&&self::$logHandle=@fopen($file,'w')){ob_start();ob_start(array(__CLASS__,'_writeFile'),1);self::_paintBlueScreen($exception);ob_end_flush();ob_end_clean();fclose(self::$logHandle);}if(self::$sendEmails){self::sendEmail((string)$exception);}}catch(Exception$e){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}echo'Nette\Debug fatal error: ',get_class($e),': ',($e->getCode()?'#'.$e->getCode().' ':'').$e->getMessage(),"\n";exit;}}elseif(self::$productionMode){}elseif(self::$consoleMode){if($outputAllowed){echo"$exception\n";}}elseif(self::$firebugDetected&&self::$ajaxDetected&&!headers_sent()){self::fireLog($exception,self::EXCEPTION);}elseif($outputAllowed){if(!headers_sent()){@ob_end_clean();while(ob_get_level()&&@ob_end_clean());if(in_array('Content-Encoding: gzip',headers_list()))header('Content-Encoding: identity',TRUE);}self::_paintBlueScreen($exception);}elseif(self::$firebugDetected&&!headers_sent()){self::fireLog($exception,self::EXCEPTION);}foreach(self::$onFatalError
as$handler){call_user_func($handler,$exception);}}public
static
function
toStringException(Exception$exception){if(self::$enabled){self::_exceptionHandler($exception);}else{trigger_error($exception->getMessage(),E_USER_ERROR);}}public
static
function
_paintBlueScreen(Exception$exception){$internals=array();foreach(array('Object','ObjectMixin')as$class){if(class_exists($class,FALSE)){$rc=new
ReflectionClass($class);$internals[$rc->getFileName()]=TRUE;}}if(class_exists('Environment',FALSE)){$application=Environment::getServiceLocator()->hasService('Nette\Application\Application',TRUE)?Environment::getServiceLocator()->getService('Nette\Application\Application'):NULL;}if(!function_exists('_netteDebugPrintCode')){function
_netteDebugPrintCode($file,$line,$count=15){if(function_exists('ini_set')){ini_set('highlight.comment','#999; font-style: italic');ini_set('highlight.default','#000');ini_set('highlight.html','#06b');ini_set('highlight.keyword','#d24; font-weight: bold');ini_set('highlight.string','#080');}$start=max(1,$line-floor($count/2));$source=@file_get_contents($file);if(!$source)return;$source=explode("\n",highlight_string($source,TRUE));$spans=1;echo$source[0];$source=explode('<br />',$source[1]);array_unshift($source,NULL);$i=$start;while(--$i>=1){if(preg_match('#.*(</?span[^>]*>)#',$source[$i],$m)){if($m[1]!=='</span>'){$spans++;echo$m[1];}break;}}$source=array_slice($source,$start,$count,TRUE);end($source);$numWidth=strlen((string)key($source));foreach($source
as$n=>$s){$spans+=substr_count($s,'<span')-substr_count($s,'</span');$s=str_replace(array("\r","\n"),array('',''),$s);if($n===$line){printf("<span class='highlight'>Line %{$numWidth}s:    %s\n</span>%s",$n,strip_tags($s),preg_replace('#[^>]*(<[^>]+>)[^<]*#','$1',$s));}else{printf("<span class='line'>Line %{$numWidth}s:</span>    %s\n",$n,$s);}}echo
str_repeat('</span>',$spans),'</code>';}function
_netteDump($dump){return'<pre class="nette-dump">'.preg_replace_callback('#(^|\s+)?(.*)\((\d+)\) <code>#','_netteDumpCb',$dump).'</pre>';}function
_netteDumpCb($m){return"$m[1]<a href='#' onclick='return !netteToggle(this)'>$m[2]($m[3]) ".(trim($m[1])||$m[3]<7?'<abbr>&#x25bc;</abbr> </a><code>':'<abbr>&#x25ba;</abbr> </a><code class="collapsed">');}function
_netteOpenPanel($name,$collapsed){static$id;$id++;?>
	<div class="panel">
		<h2><a href="#" onclick="return !netteToggle(this, 'pnl<?php echo$id?>')"><?php echo
htmlSpecialChars($name)?> <abbr><?php echo$collapsed?'&#x25ba;':'&#x25bc;'?></abbr></a></h2>

		<div id="pnl<?php echo$id?>" class="<?php echo$collapsed?'collapsed ':''?>inner">
	<?php
}function
_netteClosePanel(){?>
		</div>
	</div>
	<?php
}}static$errorTypes=array(E_ERROR=>'Fatal Error',E_USER_ERROR=>'User Error',E_RECOVERABLE_ERROR=>'Recoverable Error',E_CORE_ERROR=>'Core Error',E_COMPILE_ERROR=>'Compile Error',E_PARSE=>'Parse Error',E_WARNING=>'Warning',E_CORE_WARNING=>'Core Warning',E_COMPILE_WARNING=>'Compile Warning',E_USER_WARNING=>'User Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'User Notice',E_STRICT=>'Strict',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'User Deprecated');$title=($exception
instanceof
FatalErrorException&&isset($errorTypes[$exception->getSeverity()]))?$errorTypes[$exception->getSeverity()]:get_class($exception);$rn=0;if(headers_sent()){echo'</pre></xmp></table>';}?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,noarchive">
	<meta name="generator" content="Nette Framework">

	<title><?php echo
htmlspecialchars($title)?></title><!-- <?php echo$exception->getMessage(),($exception->getCode()?' #'.$exception->getCode():'')?> -->

	<style type="text/css">body{margin:0 0 2em;padding:0}#netteBluescreen{font:9pt/1.5 Verdana,sans-serif;background:white;color:#333;position:absolute;left:0;top:0;width:100%;z-index:23178;text-align:left}#netteBluescreen *{color:inherit;background:inherit;text-align:inherit}#netteBluescreenIcon{position:absolute;right:.5em;top:.5em;z-index:23179;text-decoration:none;background:red;padding:3px}#netteBluescreenIcon abbr{color:black!important}#netteBluescreen h1{font:18pt/1.5 Verdana,sans-serif!important;margin:.6em 0}#netteBluescreen h2{font:14pt/1.5 sans-serif!important;color:#888;margin:.6em 0}#netteBluescreen a{text-decoration:none;color:#4197E3}#netteBluescreen a abbr{font-family:sans-serif;color:#999}#netteBluescreen h3{font:bold 10pt/1.5 Verdana,sans-serif!important;margin:1em 0;padding:0}#netteBluescreen p{margin:.8em 0}#netteBluescreen pre,#netteBluescreen code,#netteBluescreen table{font:9pt/1.5 Consolas,monospace!important}#netteBluescreen pre,#netteBluescreen table{background:#fffbcc;padding:.4em .7em;border:1px dotted silver}#netteBluescreen table pre{padding:0;margin:0;border:none}#netteBluescreen pre.nette-dump span{color:#c16549}#netteBluescreen pre.nette-dump a{color:#333}#netteBluescreen div.panel{border-bottom:1px solid #eee;padding:1px 2em}#netteBluescreen div.inner{padding:.1em 1em 1em;background:#f5f5f5}#netteBluescreen table{border-collapse:collapse;width:100%}#netteBluescreen td,#netteBluescreen th{vertical-align:top;text-align:left;padding:2px 3px;border:1px solid #eeb}#netteBluescreen th{width:10%;font-weight:bold}#netteBluescreen .odd,#netteBluescreen .odd pre{background-color:#faf5c3}#netteBluescreen ul{font:7pt/1.5 Verdana,sans-serif!important;padding:1em 2em 50px}#netteBluescreen .highlight,#netteBluescreenError{background:red;color:white;font-weight:bold;font-style:normal;display:block}#netteBluescreen .line{color:#9e9e7e;font-weight:normal;font-style:normal}</style>


	<script type="text/javascript">/*<![CDATA[*/document.write("<style> .collapsed { display: none; } </style>");function netteToggle(a,b){var c=a.getElementsByTagName("abbr")[0];for(a=b?document.getElementById(b):a.nextSibling;a.nodeType!==1;)a=a.nextSibling;b=a.currentStyle?a.currentStyle.display=="none":getComputedStyle(a,null).display=="none";c.innerHTML=String.fromCharCode(b?9660:9658);a.style.display=b?a.tagName.toLowerCase()==="code"?"inline":"block":"none";return true};/*]]>*/</script>
</head>



<body>
<div id="netteBluescreen">
	<a id="netteBluescreenIcon" href="#" onclick="return !netteToggle(this)"><abbr>&#x25bc;</abbr></a

	><div>
		<div id="netteBluescreenError" class="panel">
			<h1><?php echo
htmlspecialchars($title),($exception->getCode()?' #'.$exception->getCode():'')?></h1>

			<p><?php echo
htmlspecialchars($exception->getMessage())?></p>
		</div>



		<?php $ex=$exception;$level=0;?>
		<?php do{?>

			<?php if($level++):?>
				<?php _netteOpenPanel('Caused by',$level>2)?>
				<div class="panel">
					<h1><?php echo
htmlspecialchars(get_class($ex)),($ex->getCode()?' #'.$ex->getCode():'')?></h1>

					<p><?php echo
htmlspecialchars($ex->getMessage())?></p>
				</div>
			<?php endif?>

			<?php $collapsed=isset($internals[$ex->getFile()]);?>
			<?php if(is_file($ex->getFile())):?>
			<?php _netteOpenPanel('Source file',$collapsed)?>
				<p><strong>File:</strong> <?php echo
htmlspecialchars($ex->getFile())?> &nbsp; <strong>Line:</strong> <?php echo$ex->getLine()?></p>
				<pre><?php _netteDebugPrintCode($ex->getFile(),$ex->getLine())?></pre>
			<?php _netteClosePanel()?>
			<?php endif?>



			<?php _netteOpenPanel('Call stack',FALSE)?>
				<ol>
					<?php foreach($ex->getTrace()as$key=>$row):?>
					<li><p>

					<?php if(isset($row['file'])):?>
						<span title="<?php echo
htmlSpecialChars($row['file'])?>"><?php echo
htmlSpecialChars(basename(dirname($row['file']))),'/<b>',htmlSpecialChars(basename($row['file'])),'</b></span> (',$row['line'],')'?>
					<?php else:?>
						&lt;PHP inner-code&gt;
					<?php endif?>

					<?php if(isset($row['file'])&&is_file($row['file'])):?><a href="#" onclick="return !netteToggle(this, 'src<?php echo"$level-$key"?>')">source <abbr>&#x25ba;</abbr></a>&nbsp; <?php endif?>

					<?php if(isset($row['class']))echo$row['class'].$row['type']?>
					<?php echo$row['function']?>

					(<?php if(!empty($row['args'])):?><a href="#" onclick="return !netteToggle(this, 'args<?php echo"$level-$key"?>')">arguments <abbr>&#x25ba;</abbr></a><?php endif?>)
					</p>

					<?php if(!empty($row['args'])):?>
						<div class="collapsed" id="args<?php echo"$level-$key"?>">
						<table>
						<?php

try{$r=isset($row['class'])?new
ReflectionMethod($row['class'],$row['function']):new
ReflectionFunction($row['function']);$params=$r->getParameters();}catch(Exception$e){$params=array();}foreach($row['args']as$k=>$v){echo'<tr><th>',(isset($params[$k])?'$'.$params[$k]->name:"#$k"),'</th><td>';echo
_netteDump(self::_dump($v,0));echo"</td></tr>\n";}?>
						</table>
						</div>
					<?php endif?>


					<?php if(isset($row['file'])&&is_file($row['file'])):?>
						<pre <?php if(!$collapsed||isset($internals[$row['file']]))echo'class="collapsed"';else$collapsed=FALSE?> id="src<?php echo"$level-$key"?>"><?php _netteDebugPrintCode($row['file'],$row['line'])?></pre>
					<?php endif?>

					</li>
					<?php endforeach?>

					<?php if(!isset($row)):?>
					<li><i>empty</i></li>
					<?php endif?>
				</ol>
			<?php _netteClosePanel()?>



			<?php if($ex
instanceof
IDebugPanel&&($tab=$ex->getTab())&&($panel=$ex->getPanel())):?>
			<?php _netteOpenPanel($tab,FALSE)?>
				<?php echo$panel?>
			<?php _netteClosePanel()?>
			<?php endif?>



			<?php if(isset($ex->context)&&is_array($ex->context)):?>
			<?php _netteOpenPanel('Variables',TRUE)?>
			<table>
			<?php

foreach($ex->context
as$k=>$v){echo'<tr><th>$',htmlspecialchars($k),'</th><td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";}?>
			</table>
			<?php _netteClosePanel()?>
			<?php endif?>

		<?php }while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous));?>
		<?php while(--$level)_netteClosePanel()?>



		<?php if(!empty($application)):?>
		<?php _netteOpenPanel('Nette Application',TRUE)?>
			<h3>Requests</h3>
			<?php $tmp=$application->getRequests();echo
_netteDump(self::_dump($tmp,0))?>

			<h3>Presenter</h3>
			<?php $tmp=$application->getPresenter();echo
_netteDump(self::_dump($tmp,0))?>
		<?php _netteClosePanel()?>
		<?php endif?>



		<?php _netteOpenPanel('Environment',TRUE)?>
			<?php
$list=get_defined_constants(TRUE);if(!empty($list['user'])):?>
			<h3><a href="#" onclick="return !netteToggle(this, 'pnl-env-const')">Constants <abbr>&#x25bc;</abbr></a></h3>
			<table id="pnl-env-const">
			<?php

foreach($list['user']as$k=>$v){echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th>';echo'<td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";}?>
			</table>
			<?php endif?>


			<h3><a href="#" onclick="return !netteToggle(this, 'pnl-env-files')">Included files <abbr>&#x25ba;</abbr></a>(<?php echo
count(get_included_files())?>)</h3>
			<table id="pnl-env-files" class="collapsed">
			<?php

foreach(get_included_files()as$v){echo'<tr'.($rn++%2?' class="odd"':'').'><td>',htmlspecialchars($v),"</td></tr>\n";}?>
			</table>


			<h3>$_SERVER</h3>
			<?php if(empty($_SERVER)):?>
			<p><i>empty</i></p>
			<?php else:?>
			<table>
			<?php

foreach($_SERVER
as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";?>
			</table>
			<?php endif?>
		<?php _netteClosePanel()?>



		<?php _netteOpenPanel('HTTP request',TRUE)?>
			<?php if(function_exists('apache_request_headers')):?>
			<h3>Headers</h3>
			<table>
			<?php

foreach(apache_request_headers()as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',htmlspecialchars($v),"</td></tr>\n";?>
			</table>
			<?php endif?>


			<?php foreach(array('_GET','_POST','_COOKIE')as$name):?>
			<h3>$<?php echo$name?></h3>
			<?php if(empty($GLOBALS[$name])):?>
			<p><i>empty</i></p>
			<?php else:?>
			<table>
			<?php

foreach($GLOBALS[$name]as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";?>
			</table>
			<?php endif?>
			<?php endforeach?>
		<?php _netteClosePanel()?>



		<?php _netteOpenPanel('HTTP response',TRUE)?>
			<h3>Headers</h3>
			<?php if(headers_list()):?>
			<pre><?php

foreach(headers_list()as$s)echo
htmlspecialchars($s),'<br>';?></pre>
			<?php else:?>
			<p><i>no headers</i></p>
			<?php endif?>
		<?php _netteClosePanel()?>


		<ul>
			<li>Report generated at <?php echo@date('Y/m/d H:i:s',self::$time)?></li>
			<?php if(isset($_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI'])):?>
				<li><a href="<?php $url=(isset($_SERVER['HTTPS'])&&strcasecmp($_SERVER['HTTPS'],'off')?'https://':'http://').htmlSpecialChars($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])?>"><?php echo$url?></a></li>
			<?php endif?>
			<li>PHP <?php echo
htmlSpecialChars(PHP_VERSION)?></li>
			<?php if(isset($_SERVER['SERVER_SOFTWARE'])):?><li><?php echo
htmlSpecialChars($_SERVER['SERVER_SOFTWARE'])?></li><?php endif?>
			<?php if(class_exists('Framework')):?><li><?php echo
htmlSpecialChars('Nette Framework '.Framework::VERSION)?> <i>(revision <?php echo
htmlSpecialChars(Framework::REVISION)?>)</i></li><?php endif?>
		</ul>
	</div>
</div>

<script type="text/javascript">document.body.appendChild(document.getElementById("netteBluescreen"));</script>
</body>
</html><?php }public
static
function
_writeFile($buffer){fwrite(self::$logHandle,$buffer);}private
static
function
sendEmail($message){$monitorFile=self::$logFile.'.monitor';if(@filemtime($monitorFile)+self::$emailSnooze<time()&&@file_put_contents($monitorFile,'sent')){call_user_func(self::$mailer,$message);}}private
static
function
defaultMailer($message){$host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'');$headers=str_replace(array('%host%','%date%','%message%'),array($host,@date('Y-m-d H:i:s',self::$time),$message),self::$emailHeaders);$subject=$headers['Subject'];$to=$headers['To'];$body=$headers['Body'];unset($headers['Subject'],$headers['To'],$headers['Body']);$header='';foreach($headers
as$key=>$value){$header.="$key: $value\r\n";}$body=str_replace("\r\n","\n",$body);if(PHP_OS!='Linux')$body=str_replace("\n","\r\n",$body);mail($to,$subject,$body,$header);}public
static
function
addPanel(IDebugPanel$panel){self::$panels[]=$panel;}public
static
function
renderTab($id){switch($id){case'time':?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ6SURBVDjLjZO7T1NhGMY7Mji6uJgYt8bElTjof6CDg4sMSqIxJsRGB5F4TwQSIg1QKC0KWmkZEEsKtEcSxF5ohV5pKSicXqX3aqGn957z+PUEGopiGJ583/A+v3znvPkJAAjWR0VNJG0kGhKahCFhXcN3YBFfx8Kry6ym4xIzce88/fbWGY2k5WRb77UTTbWuYA9gDGg7EVmSIOF4g5T7HZKuMcSW5djWDyL0uRf0dCc8inYYxTcw9fAiCMBYB3gVj1z7gLhNTjKCqHkYP79KENC9Bq3uxrrqORzy+9D3tPAAccspVx1gWg0KbaZFbGllWFM+xrKkFQudV0CeDfJsjN4+C2nracjunoPq5VXIBrowMK4V1gG1LGyWdbZwCalsBYUyh2KFQzpXxVqkAGswD3+qBDpZwow9iYE5v26/VwfUQnnznyhvjguQYabIIpKpYD1ahI8UTT92MUSFuP5Z/9TBTgOgFrVjp3nakaG/0VmEfpX58pwzjUEquNk362s+PP8XYD/KpYTBHmRg9Wch0QX1R80dCZhYipudYQY2Auib8RmODVCa4hfUK4ngaiiLNFNFdKeCWWscXZMbWy9Unv9/gsIQU09a4pwvUeA3Uapy2C2wCKXL0DqTePLexbWPOv79E8f0UWrencZ2poxciUWZlKssB4bcHeE83NsFuMgpo2iIpMuNa1TNu4XjhggWvb+R2K3wZdLlAZl8Fd9jRb5sD+Xx0RJBx5gdom6VsMEFDyWF0WyCeSOFcDKPnRxZYTQL5Rc/nn1w4oFsBaIhC3r6FRh5erPRhYMyHdeFw4C6zkRhmijM7CnMu0AUZonCDCnRJBqSus5/ABD6Ba5CkQS8AAAAAElFTkSuQmCC"
><?php echo
number_format((microtime(TRUE)-self::$time)*1000,1,'.',' ')?>ms
<?php
return;case'memory':?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGvSURBVDjLpZO7alZREEbXiSdqJJDKYJNCkPBXYq12prHwBezSCpaidnY+graCYO0DpLRTQcR3EFLl8p+9525xgkRIJJApB2bN+gZmqCouU+NZzVef9isyUYeIRD0RTz482xouBBBNHi5u4JlkgUfx+evhxQ2aJRrJ/oFjUWysXeG45cUBy+aoJ90Sj0LGFY6anw2o1y/mK2ZS5pQ50+2XiBbdCvPk+mpw2OM/Bo92IJMhgiGCox+JeNEksIC11eLwvAhlzuAO37+BG9y9x3FTuiWTzhH61QFvdg5AdAZIB3Mw50AKsaRJYlGsX0tymTzf2y1TR9WwbogYY3ZhxR26gBmocrxMuhZNE435FtmSx1tP8QgiHEvj45d3jNlONouAKrjjzWaDv4CkmmNu/Pz9CzVh++Yd2rIz5tTnwdZmAzNymXT9F5AtMFeaTogJYkJfdsaaGpyO4E62pJ0yUCtKQFxo0hAT1JU2CWNOJ5vvP4AIcKeao17c2ljFE8SKEkVdWWxu42GYK9KE4c3O20pzSpyyoCx4v/6ECkCTCqccKorNxR5uSXgQnmQkw2Xf+Q+0iqQ9Ap64TwAAAABJRU5ErkJggg=="
><?php echo
number_format(memory_get_peak_usage()/1000,1,'.',' ')?> kB
<?php
return;case'dumps':if(!Debug::$dumps)return;?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIASURBVDjLpVPPaxNREJ6Vt01caH4oWk1T0ZKlGIo9RG+BUsEK4kEP/Q8qPXnpqRdPBf8A8Wahhx7FQ0GF9FJ6UksqwfTSBDGyB5HkkphC9tfb7jfbtyQQTx142byZ75v5ZnZWC4KALmICPy+2DkvKIX2f/POz83LxCL7nrz+WPNcll49DrhM9v7xdO9JW330DuXrrqkFSgig5iR2Cfv3t3gNxOnv5BwU+eZ5HuON5/PMPJZKJ+yKQfpW0S7TxdC6WJaWkyvff1LDaFRAeLZj05MHsiPTS6hua0PUqtwC5sHq9zv9RYWl+nu5cETcnJ1M0M5WlWq3GsX6/T+VymRzHDluZiGYAAsw0TQahV8uyyGq1qFgskm0bHIO/1+sx1rFtchJhArwEyIQ1Gg2WD2A6nWawHQJVDIWgIJfLhQowTIeE9D0mKAU8qPC0220afsWFQoH93W6X7yCDJ+DEBeBmsxnPIJVKxWQVUwry+XyUwBlKMKwA8jqdDhOVCqVAzQDVvXAXhOdGBFgymYwrGoZBmUyGjxCCdF0fSahaFdgoTHRxfTveMCXvWfkuE3Y+f40qhgT/nMitupzApdvT18bu+YeDQwY9Xl4aG9/d/URiMBhQq/dvZMeVghtT17lSZW9/rAKsvPa/r9Fc2dw+Pe0/xI6kM9mT5vtXy+Nw2kU/5zOGRpvuMIu0YAAAAABJRU5ErkJggg==">variables
<?php
return;case'errors':if(!Debug::$errors)return;?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIsSURBVDjLpVNLSJQBEP7+h6uu62vLVAJDW1KQTMrINQ1vPQzq1GOpa9EppGOHLh0kCEKL7JBEhVCHihAsESyJiE4FWShGRmauu7KYiv6Pma+DGoFrBQ7MzGFmPr5vmDFIYj1mr1WYfrHPovA9VVOqbC7e/1rS9ZlrAVDYHig5WB0oPtBI0TNrUiC5yhP9jeF4X8NPcWfopoY48XT39PjjXeF0vWkZqOjd7LJYrmGasHPCCJbHwhS9/F8M4s8baid764Xi0Ilfp5voorpJfn2wwx/r3l77TwZUvR+qajXVn8PnvocYfXYH6k2ioOaCpaIdf11ivDcayyiMVudsOYqFb60gARJYHG9DbqQFmSVNjaO3K2NpAeK90ZCqtgcrjkP9aUCXp0moetDFEeRXnYCKXhm+uTW0CkBFu4JlxzZkFlbASz4CQGQVBFeEwZm8geyiMuRVntzsL3oXV+YMkvjRsydC1U+lhwZsWXgHb+oWVAEzIwvzyVlk5igsi7DymmHlHsFQR50rjl+981Jy1Fw6Gu0ObTtnU+cgs28AKgDiy+Awpj5OACBAhZ/qh2HOo6i+NeA73jUAML4/qWux8mt6NjW1w599CS9xb0mSEqQBEDAtwqALUmBaG5FV3oYPnTHMjAwetlWksyByaukxQg2wQ9FlccaK/OXA3/uAEUDp3rNIDQ1ctSk6kHh1/jRFoaL4M4snEMeD73gQx4M4PsT1IZ5AfYH68tZY7zv/ApRMY9mnuVMvAAAAAElFTkSuQmCC"
><span class="nette-warning"><?php echo
count(self::$errors)?> errors</span>
<?php }}public
static
function
renderPanel($id){switch($id){case'dumps':if(!function_exists('_netteDumpCb2')){function
_netteDumpCb2($m){return"$m[1]<a href='#' class='nette-toggler'>$m[2]($m[3]) ".($m[3]<7?'<abbr>&#x25bc;</abbr> </a><code>':'<abbr>&#x25ba;</abbr> </a><code class="nette-hidden">');}}?>
<style>#nette-debug-dumps h2{font:11pt/1.5 sans-serif;margin:0;padding:2px 8px;background:#3484d2;color:white}#nette-debug-dumps table{width:100%}#nette-debug #nette-debug-dumps a{color:#333;background:transparent}#nette-debug-dumps a abbr{font-family:sans-serif;color:#999}#nette-debug-dumps pre.nette-dump span{color:#c16549}</style>


<h1>Dumped variables</h1>

<div class="nette-inner">
<?php foreach(self::$dumps
as$item):?>
	<?php if($item['title']):?>
	<h2><?php echo
htmlspecialchars($item['title'])?></h2>
	<?php endif?>

	<table>
	<?php $i=0?>
	<?php foreach($item['dump']as$key=>$dump):?>
	<tr class="<?php echo$i++%
2?'nette-alt':''?>">
		<th><?php echo
htmlspecialchars($key)?></th>
		<td><?php echo
preg_replace_callback('#(<pre class="nette-dump">|\s+)?(.*)\((\d+)\) <code>#','_netteDumpCb2',$dump)?></td>
	</tr>
	<?php endforeach?>
	</table>
<?php endforeach?>
</div>
<?php
return;case'errors':?><h1>Errors</h1>

<?php $relative=isset($_SERVER['SCRIPT_FILENAME'])?strtr(dirname(dirname($_SERVER['SCRIPT_FILENAME'])),'/',DIRECTORY_SEPARATOR):NULL?>

<div class="nette-inner">
<table>
<?php foreach(self::$errors
as$i=>$item):?>
<tr class="<?php echo$i++%
2?'nette-alt':''?>">
	<td><pre><?php echo$relative?str_replace($relative,"...",$item):$item?></pre></td>
</tr>
<?php endforeach?>
</table>
</div><?php }}public
static
function
fireLog($message,$priority=self::LOG,$label=NULL){if($message
instanceof
Exception){if($priority!==self::EXCEPTION&&$priority!==self::TRACE){$priority=self::TRACE;}$message=array('Class'=>get_class($message),'Message'=>$message->getMessage(),'File'=>$message->getFile(),'Line'=>$message->getLine(),'Trace'=>$message->getTrace(),'Type'=>'','Function'=>'');foreach($message['Trace']as&$row){if(empty($row['file']))$row['file']='?';if(empty($row['line']))$row['line']='?';}}elseif($priority===self::GROUP_START){$label=$message;$message=NULL;}return
self::fireSend('FirebugConsole/0.1',self::replaceObjects(array(array('Type'=>$priority,'Label'=>$label),$message)));}private
static
function
fireSend($struct,$payload){if(self::$productionMode)return
NULL;if(headers_sent())return
FALSE;header('X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');header('X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');static$structures;$index=isset($structures[$struct])?$structures[$struct]:($structures[$struct]=count($structures)+1);header("X-Wf-nette-Structure-$index: http://meta.firephp.org/Wildfire/Structure/FirePHP/$struct");$payload=json_encode($payload);static$counter;foreach(str_split($payload,4990)as$s){$num=++$counter;header("X-Wf-nette-$index-1-n$num: |$s|\\");}header("X-Wf-nette-$index-1-n$num: |$s|");return
TRUE;}static
private
function
replaceObjects($val){if(is_object($val)){return'object '.get_class($val).'';}elseif(is_string($val)){return@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$val));}elseif(is_array($val)){foreach($val
as$k=>$v){unset($val[$k]);$k=@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$k));$val[$k]=self::replaceObjects($v);}}return$val;}}interface
IDebugPanel{function
getTab();function
getPanel();function
getId();}class
DebugPanel
implements
IDebugPanel{private$id;private$tabCb;private$panelCb;public
function
__construct($id,$tabCb,$panelCb){$this->id=$id;$this->tabCb=$tabCb;$this->panelCb=$panelCb;}public
function
getId(){return$this->id;}public
function
getTab(){ob_start();call_user_func($this->tabCb,$this->id);return
ob_get_clean();}public
function
getPanel(){ob_start();call_user_func($this->panelCb,$this->id);return
ob_get_clean();}}Debug::_init();if(!function_exists('dump')){function
dump($var){foreach($args=func_get_args()as$arg)Debug::dump($arg);return$var;}}class
FatalErrorException
extends
Exception{private$severity;public
function
__construct($message,$code,$severity,$file,$line,$context){parent::__construct($message,$code);$this->severity=$severity;$this->file=$file;$this->line=$line;$this->context=$context;}public
function
getSeverity(){return$this->severity;}}