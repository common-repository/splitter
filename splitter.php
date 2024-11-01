<?
/*
Plugin Name: Splitter
Plugin URI: http://www.gig.ru
Description: Allows to automatically split a post into pages by &lt;!--nextpage--&gt; tag, with html validity maintenance
Version: 0.2
Author: UM
Author URI: http://www.gig.ru
Text Domain: splitter
Domain Path: /lang
*/

if (!class_exists('Splitter')) {
  
    class Splitter {

	   	var $processed=FALSE;
 
        function Splitter() { $this->__construct(); }

        function __construct() {
            add_action('admin_menu',array(&$this,'createSplitterField'));
            add_action('save_post',array(&$this,'processSplitterField'),1,2);
//            add_action('do_meta_boxes',array(&$this,'removeDefaultCustomFields'),10,3);
			$plugin_dir=trailingslashit(WP_CONTENT_DIR.'/plugins/'.plugin_basename(dirname(__FILE__)));
			$locale=get_locale();
			$mofile=$plugin_dir.'lang/splitter-'.$locale.'.mo';
			load_textdomain('splitter',$mofile);
        }  

        function removeDefaultCustomFields($type,$context,$post) {
            foreach (array('normal','advanced','side') as $context) {
                remove_meta_box('postcustom','post',$context);
                remove_meta_box('postcustom','page',$context);
            }  
        }  

        function createSplitterField() {
			if (!current_user_can('manage_options')) { return; }
            if (function_exists('add_meta_box')) {
                add_meta_box('splitter','Splitter',array(&$this,'displaySplitterField'),'post','normal','high');
            }  
        }  

        function displaySplitterField() {
            global $post;
            ?>
            <div class="form-wrap">
                <?php
            	wp_nonce_field('splitter','splitter_wpnonce',false,true);
            	if ((basename($_SERVER['SCRIPT_FILENAME'])=="post-new.php"||$post->post_type=="post")&&current_user_can('edit_posts',$post->ID)) {
				?>
                    <div class="form-field form-required">
                        <label><b><?php _e('Paginate automatically','splitter'); ?></b></label><br />
                        <input type="radio" name="splitter_split" value="0" checked="checked" style="width: 20px;" /><?php _e('don\'t paginate','splitter'); ?><br />
                        <input type="radio" name="splitter_split" value="1" style="width: 20px;" /><?php _e('split with separator','splitter'); ?>: <input type="text" name="splitter_separator" size="3" value="--" style="width: 50px;" /> (<?php _e('without html validation','splitter'); ?>)<br />
                        <input type="radio" name="splitter_split" value="2" style="width: 20px;" /><?php printf(__('after every %s chars (1500 min.)','splitter'),'<input type="text" name="splitter_chunk_size" size="3" value="5000" style="width: 50px;" />'); ?>
                    </div>
                <?php
                }
                ?>
            </div>
            <?php
        }

        function processSplitterField($post_id,$post) {
        	if ($this->processed)
        		return;
            if (!wp_verify_nonce($_POST['splitter_wpnonce'],'splitter'))
                return;
            if (!current_user_can('edit_post',$post_id))
                return;
            if ($post->post_type!='post')
                return;
			$data=$post->post_content;
			if ($data) {
            	$this->processed=TRUE;
				if ($_POST['splitter_split']==1&&$_POST['splitter_separator']) {
					$value=preg_replace('/([\r\n])'.$_POST['splitter_separator'].'([\r\n])/','\\1<!--nextpage-->\\2',$value);
				} else if ($_POST['splitter_split']==2&&$_POST['splitter_chunk_size']>0) {
					if ($_POST['splitter_chunk_size']<1500) { $_POST['splitter_chunk_size']=1500; }
					$data=$this->html_split($data,$_POST['splitter_chunk_size']);
				}
				$update_post=array(
					'ID'=>$post->ID,
					'post_content'=>$data
				);
				$result=wp_update_post($update_post);
            }
            return;
        }

		function html_split($html,$chunk_size=5000,$separator='<!--nextpage-->',$dont_split_within=array('h1','h2','h3','h4','h5','a','u','b','strong','table','form')) {
			$html=preg_replace('#<((area|base|basefont|br|col|frame|hr|img|input|link|meta|param)( [^>]*[^/])?)>#si','<\\1 />',$html);
			$result='';
			$debug=0;
			while (strlen($html)>$chunk_size) {
//				if ($debug) echo "============\n";
				$chunk=substr($html,0,$chunk_size);
//				if ($debug) echo "HTML IN:\t$html\n";
//				if ($debug) echo "CHUNK IN1:\t>>>$chunk<<<\n";
				if (preg_match('#^(.*)<(/|[a-z])?[^>]*'.'$'.'#sim',$chunk,$arr)) {
					$chunk=$arr[1];
//					if ($debug) echo "CHUNK IN2:\t>>>$chunk<<<\n";
				}
				$html=substr($html,strlen($chunk));
		
				$matches=0;
				while (preg_match('#(<(/?)([a-z]+\d?)( [^>]*([^/]))?>|\.| )([^\. <>]*)'.'$'.'#si',$chunk,$arr)) {
					$matches++;
//					if ($debug) print_r($arr);
					if ($arr[1]!='.'&&$arr[1]!=' ') {
						if ($arr[2]!='/'&&$arr[5]!='/') {
							$html=$arr[0].$html;
							$chunk=substr($chunk,0,strlen($chunk)-strlen($arr[0]));
//							if ($debug) echo "-next-\n";
						} else {
							$html=$arr[6].$html;
							$chunk=substr($chunk,0,strlen($chunk)-strlen($arr[6]));
//							if ($debug) echo "-break1-\n";
							break;
						}
					} else {
						if (strlen($arr[6])) {
							$html=$arr[6].$html;
							$chunk=substr($chunk,0,strlen($chunk)-strlen($arr[6]));
						}
//						if ($debug) echo "-break2-\n";
						break;
					}
				}
//				if ($debug) echo "MATCHES: $matches\n";
//				if ($debug) echo "CHUNK IN3:\t>>>$chunk<<<\n";
				
				$tags_orig=array();
				$tags=array();
				$chunk_tmp=$chunk;
				
				while ($chunk_tmp>'') {
					if (preg_match('#(<(/?)([a-z]+\d?)( [^>]*[^/])?>)(.*)'.'$'.'#si',$chunk_tmp,$arr)) {
//						if ($debug) echo "Tag found: $arr[1]";
						$chunk_tmp=$arr[5];
						$arr[3]=strtolower($arr[3]);
						if ($arr[2]!='/') {
//							if ($debug) echo " - opening/single - added ";
							array_push($tags_orig,$arr[1]);
							array_push($tags,$arr[3]);
						} else {
//							if ($debug) echo " - closing";
							$found=false;
							for ($i=@count($tags)-1; $i>=0; $i--) {
								if ($tags[$i]==$arr[3]) { $found=true; break; }
							}
							if ($found) {
								if ($i) {
									array_splice($tags,-$i);
									array_splice($tags_orig,-$i);
								} else {
									$tags=array();
									$tags_orig=array();
								}
//								if ($debug) echo " - removed";
							} else {
//								if ($debug) echo " - not found";
							}
						}
//						if ($debug) echo "\n";
//						if ($debug) print_r($tags);
					} else {
						$chunk_tmp='';
					}
				}
				if (@count($tags)) {
//					if ($debug) print_r($tags_orig);
					$chunk.='</'.implode('></',array_reverse($tags)).'>';
				}
				
//				if ($debug) echo "CHUNK OUT:\t>>>$chunk<<<\n";
				$result.=($result>''?$separator:'').$chunk;
				if (@count($tags_orig)>0) { $html=implode('',$tags_orig).$html; }
//				if ($debug) echo "HTML OUT:\t$html\n";
//				if ($debug) echo "============\n";
			}
			if (strlen($html)) {
				$result.=($result>''?$separator:'').$html;
			}
//			if ($debug) die('--- DIE ---');
			return $result;
		}
        
    }
}
  
if (class_exists('Splitter')) {  
    $Splitter_var = new Splitter();  
}

?>