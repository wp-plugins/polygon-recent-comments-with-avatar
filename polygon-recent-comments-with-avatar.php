<?php
/*
  Plugin Name: Polygon recent comments with avatar
  Plugin URI: http://cafefreelancer.com/vi/wordpress/wordpress-plugins/polygon-plugins/
  Description: Recent comments with image avatar, support gravatar, date, user
  Version: 1.0
  Author: CafeFreelancer.com
  Author URI: http://CafeFreelancer.com
  License: GPLv2
 */

require_once(ABSPATH . WPINC . '/default-widgets.php');

function POLYGON_Recent_Comments() {
    register_widget("POLYGON_Widget_Recent_Comments");
}
add_action("widgets_init", "POLYGON_Recent_Comments");

class POLYGON_Widget_Recent_Comments extends WP_Widget {
    function __construct() {
        parent::__construct(
                'Polygon_widget',
                __('Polygon recent comments with avatar', 'POLYGON_Widget_Recent_Comments'),
                array('description' => __('This widget support display avatar, date, comment...', 'POLYGON_Widget_Recent_Comments'),)
        );
    }

    function widget($args, $instance) {
        global $comments, $comment;

        $cache = wp_cache_get('widget_polygon_recent_comments', 'widget');

        if (!is_array($cache))
            $cache = array();

        if (!isset($args['widget_id']))
            $args['widget_id'] = $this->id;

        if (isset($cache[$args['widget_id']])) {
            echo $cache[$args['widget_id']];
            return;
        }
        extract($args, EXTR_SKIP);
        $output = '';
        $title = apply_filters('widget_title', empty($instance['title']) ? __('Polygon recent comments', 'POLYGON_Widget_Recent_Comments') : $instance['title'], $instance, $this->id_base);
        $num_comments = (isset($instance['num_comments']) ) ? $instance['num_comments'] : 5;
        $avatar_size = (isset($instance['avatar_size']) ) ? $instance['avatar_size'] : 88;
        $num_split= (isset($instance['num_split']) ) ? $instance['num_split'] : 121;
        $show_date = (isset($instance['show_date'] )) ? $instance['show_avatar'] : 'false';
        $show_avatar = (isset($instance['show_avatar'])) ? $instance['show_avatar'] : 'false';
        $show_gravatar = (isset($instance['show_gravatar'])) ? $instance['show_gravatar'] : 'false';
        $avatar_layout = (isset($instance['avatar_layout']) ) ? $instance['avatar_layout'] : 'square';
        $height_show = (isset($instance['height_show']) ) ? $instance['height_show'] : 200;
        //Get comments;
        $comments = get_comments(apply_filters('widget_comments_args', array('number' => $num_comments, 'status' => 'approve', 'post_status' => 'publish', 'type' => 'comment')));

        if (!empty($comments)){
            $output .= $before_widget;
            if ($title)
                $output .= $before_title . $title . $after_title;

            $output .= '<ul id="recentcomments">';
            if ($comments) {
                ?>
<style type="text/css">ul#recentcomments {list-style: none;padding: 0;margin: 0;max-height:<?php echo $height_show;?>px;overflow-y:auto;overflow-x:hidden}ul#recentcomments li.recentcomments {border-bottom: 1px solid #C6C6C6;margin: 0 0 8px;padding: 0 0 9px;background-image: none;list-style: none;display: inline-block}ul#recentcomments .alignleft { margin: 0 8px 0 0;padding: 0}ul#recentcomments img.avatar {background-color: #FFFFFF;border: 1px solid #C6C6C6;box-shadow: none;padding: 4px;margin: 0}ul#recentcoments .date{color:#CCC;font-size:12px;font-style:italic}.circle{border-radius: 50%;}.square{border-radius: 0%;}.eclip1{border-top-left-radius: 50%; border-top-right-radius: 0%; border-bottom-right-radius: 50%; border-bottom-left-radius: 0%;}.eclip2{border-top-left-radius: 0%; border-top-right-radius: 50%; border-bottom-right-radius: 0%; border-bottom-left-radius: 50%;}.eclip3{border-radius: 20% 50%;}.eclip4{border-radius: 50% 20%;}</style>
<?php
                    if (!function_exists('polygon_validate_gravatar')){
			function polygon_validate_gravatar($email) {
				$hash = md5(strtolower(trim($email)));
				$uri = 'http://www.gravatar.com/avatar/' . $hash . '?d=404';
				$headers = @get_headers($uri);
				if (!preg_match("|200|", $headers[0])) {
					$has_valid_avatar = FALSE;
				}
				else {
					$has_valid_avatar = TRUE;
				}
				return $has_valid_avatar;
			}
                    }

                // Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
                $post_ids = array_unique(wp_list_pluck($comments, 'comment_post_ID'));
                _prime_post_caches($post_ids, strpos(get_option('permalink_structure'), '%category%'), false);
				$matchSrc = "/src=[\"' ]?([^\"' >]+)[\"' ]?[^>]*>/i" ;
                foreach ((array) $comments as $comment) {
                    $post_title_current = get_the_title($comment->comment_post_ID);
                    //Format date
                    $d = "d/m/Y";
                    //Format time
                    $t = "g:i A";
					
		$email=$comment->comment_author_email;
                    //Display Gravatar;
                    if ($show_gravatar == true && polygon_validate_gravatar($email)) {
                        $avatar = get_Gravatar_Author($email, $avatar_size);
                    } else {
                        $avatar = get_avatar($email, $avatar_size);
                    }

                    preg_match($matchSrc, $avatar, $matches);
					$theImageUrl = $matches[1];
					
                    $cus_comment_date = get_comment_date($d, $comment->comment_ID) . __(' at ') . get_comment_date($t, $comment->comment_ID);
                    $output .= '<li class="recentcomments">';
                    $output.=($show_avatar==true)? '<div class="alignleft"><img src="'.$theImageUrl.'" width="'.$avatar_size.'" height="'.$avatar_size.'" class="'.$avatar_layout.'" style="float: left; margin-right: 10px;  background-color: rgb(255, 255, 255); padding: 3px; border: 1px solid rgb(214, 214, 214); width: '.$avatar_size.'px; height: '.$avatar_size.'px;" /></div>':'';
                    $output .='<b>' . get_comment_author_link() . '</b> '.__('on') . ' <a href="' . esc_url(get_comment_link($comment->comment_ID)) . '">' . $post_title_current . '</a> ';
                    $cur_comment=strip_tags($comment->comment_content);
                    $output .=mb_substr($cur_comment, 0, $num_split);
                    if ($show_date == true) {
                        $output.= ' <span class="date">(' . $cus_comment_date . ')</span>';
                    }
                    $output.= '</li>';
                }
            }
            $output .= '</ul>';
            $output .= $after_widget;
            echo $output;
            $cache[$args['widget_id']] = $output;
            wp_cache_set('widget_polygon_recent_comments', $cache, 'widget');
        }
    }

    public function form($instance) {

        $title = ( !empty($instance['title']) ) ? $instance['title'] : __('Polygon recent comments', 'POLYGON_Widget_Recent_Comments');
        $num_comments = (!empty($instance['num_comments']) ) ? $instance['num_comments'] : 0;
        $avatar_size = (!empty($instance['avatar_size']) ) ? $instance['avatar_size'] : 88;
        $num_split= (!empty($instance['num_split']) ) ? $instance['num_split'] : 121;
         $avatar_layout = (isset($instance['avatar_layout']))?((!empty($instance['avatar_layout']) ) ? $instance['avatar_layout'] : 'square'):'square';
        $show_date = (!empty($instance['show_date']) ) ? $instance['show_date'] : 'false';
        $height_show = (isset($instance['height_show']))?((!empty($instance['height_show']) ) ? $instance['height_show'] : 200):200;
        $show_avatar = (!empty($instance['show_avatar']) ) ? $instance['show_avatar'] : 'false';
        $show_gravatar = (!empty($instance['show_gravatar']) ) ? $instance['show_gravatar'] : 'false';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            <label for="<?php echo $this->get_field_id('num_comments'); ?>"><?php _e('Number comments (<=50):'); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id('num_comments'); ?>" name="<?php echo $this->get_field_name('num_comments'); ?>" type="text" value="<?php echo esc_attr($num_comments); ?>" />
<label for="<?php echo $this->get_field_id('num_split'); ?>"><?php _e('Number characters:'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('num_split'); ?>" name="<?php echo $this->get_field_name('num_split'); ?>" type="text" value="<?php echo esc_attr($num_split); ?>" />
<input class="widefat" id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>" type="checkbox" <?php checked($instance['show_date'], 'on'); ?> />
<label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show date ?'); ?></label> <br/>
<input class="widefat" id="<?php echo $this->get_field_id('show_avatar'); ?>" name="<?php echo $this->get_field_name('show_avatar'); ?>" type="checkbox" <?php checked($instance['show_avatar'], 'on'); ?> />
<label for="<?php echo $this->get_field_id('show_avatar'); ?>"><?php _e('Show avatar ?'); ?></label>
<br/>
<input class="widefat" id="<?php echo $this->get_field_id('show_gravatar'); ?>" name="<?php echo $this->get_field_name('show_gravatar'); ?>" type="checkbox" <?php checked($instance['show_gravatar'], 'on'); ?> />
<label for="<?php echo $this->get_field_id('show_gravatar'); ?>"><?php _e('Show gravatar ? (show avatar checked require)'); ?></label>
            
            <br/>
            <label for="<?php echo $this->get_field_id('avatar_size'); ?>"><?php _e('Avatar size:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('avatar_size'); ?>" name="<?php echo $this->get_field_name('avatar_size'); ?>" type="text" value="<?php echo esc_attr($avatar_size); ?>" /><br/>
             <label for="<?php echo $this->get_field_id('avatar_layout'); ?>"><?php _e('Style:'); ?></label> 
            <select name="<?php echo $this->get_field_name('avatar_layout'); ?>" id="<?php echo $this->get_field_id('avatar_layout'); ?>">
                <option value='square' <?php echo ($avatar_layout == 'square') ? 'selected' : '' ?>>Square</option>
                <option value='circle'  <?php echo ($avatar_layout == 'circle') ? 'selected' : '' ?>>Circle</option>
                <option value='eclip1'  <?php echo ($avatar_layout == 'eclip1') ? 'selected' : '' ?>>Eclip 1</option>
                <option value='eclip2'  <?php echo ($avatar_layout == 'eclip2') ? 'selected' : '' ?>>Eclip 2</option>
                <option value='eclip3'  <?php echo ($avatar_layout == 'eclip3') ? 'selected' : '' ?>>Eclip 3</option>
                <option value='eclip4'  <?php echo ($avatar_layout == 'eclip4') ? 'selected' : '' ?>>Eclip 4</option>
            </select>
            <br/>
            
            <label for="<?php echo $this->get_field_id('height_show'); ?>"><?php _e('Height show (for scroll):'); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id('height_show'); ?>" name="<?php echo $this->get_field_name('height_show'); ?>" type="text" value="<?php echo esc_attr($height_show); ?>" />
        <br/></p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = (!empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        $instance['num_comments'] = (!empty($new_instance['num_comments']) ) ? ($new_instance['num_comments'] <= 50) ? $new_instance['num_comments'] : 12 : '';
        $instance['num_split'] = (!empty($new_instance['num_split']) ) ? ($new_instance['num_split'] <= 250) ? $new_instance['num_split'] : 121 : '';
        $instance['show_date'] = strip_tags($new_instance['show_date']);
        $instance['show_avatar'] = strip_tags($new_instance['show_avatar']);
        $instance['show_gravatar'] = strip_tags($new_instance['show_gravatar']);
        $instance['avatar_layout'] =  $new_instance['avatar_layout'];
        $instance['avatar_size'] = (!empty($new_instance['avatar_size']) ) ? strip_tags($new_instance['avatar_size']) : '88';
        $instance['height_show']= (!empty($new_instance['height_show']) ) ? ($new_instance['height_show'] <= 500) ? $new_instance['height_show'] : 200 : 200;
        return $instance;
    }

}
