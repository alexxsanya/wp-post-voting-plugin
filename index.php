<?php 
    // creates a function to handle article voting

    /*
    Plugin Name: Post Vote 
    Plugin URI: https://github.com/alexxsanya/wp-post-vote-plugin
    description: A plugin to vote an article
    Version: 1.0
    Author: alexxsanya
    Author URI: http://alexxsanya.dev
    License: GPL2
    */
    
    /*
        The pllugin stores the number of times each post has been viewed.
        Styling is based on bootstrap 4.0
    */

    /**
         * Adds a view to the post being viewed
         *
         * Finds the current views of a post and adds one to it by updating
         * the postmeta. The meta key used is "awepop_views".
         *
         * @global object $post The post object
         * @return integer $new_views The number of views the post has
         *
    */

    add_action("wp_ajax_my_user_vote", "my_user_vote");
    add_action("wp_ajax_nopriv_my_user_vote", "my_must_login");

    function my_user_vote() {

        if ( !wp_verify_nonce( $_REQUEST['nonce'], "my_user_vote_nonce")) {
            exit("No naughty business please");
        }   

        $vote_count = get_post_meta($_REQUEST["post_id"], "votes", true);

        $vote_count = ($vote_count == ’) ? 0 : $vote_count;
        $new_vote_count = $vote_count + 1;

        $vote = update_post_meta($_REQUEST["post_id"], "votes", $new_vote_count);
        $user = update_user_meta(get_current_user_id(), "voted", true);

        if($vote === false) {
            $result['type'] = "error";
            $result['vote_count'] = $vote_count;
        }
        else {
            $result['type'] = "success";
            $result['vote_count'] = $new_vote_count;
        }

        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
        }
        else {
            header("Location: ".$_SERVER["HTTP_REFERER"]);
        }

        die();

    }

    function my_must_login() {
        echo "You must log in to vote";
        die();
    }

    function voter_status() {
        // checks if a voter status to ensure voter votes only once
        $user_id = get_current_user_id();

        $voter_status = get_user_meta($user_id, "voted", true);
    
        return $voter_status;
    }

    add_action( 'init', 'my_script_enqueuer' );

    function my_script_enqueuer() {
        wp_register_script( "my_voter_script", WP_PLUGIN_URL.'/post-voting/js/voting.js', array('jquery') );
        wp_localize_script( 'my_voter_script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));        

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'my_voter_script' );

    }

    function voting_ui(){
        global $post;
        $votes = get_post_meta($post->ID, "votes", true);
        
        $votes = ($votes == "") ? 0 : $votes;
        echo "<div id='vote_counter'>This post has <span class='badge badge-primary'>$votes</span> votes</div>";

        $nonce = wp_create_nonce("my_user_vote_nonce");
        if(!voter_status()) {
            $link = admin_url('admin-ajax.php?action=my_user_vote&post_id='.$post->ID.'&nonce='.$nonce);
            echo '<a class="user_vote btn btn-warning" data-nonce="' . $nonce . '" data-post_id="' . $post->ID . '" href="' . $link . '">vote for this article</a>';
        }else {
            echo '<small>You already voted</small>';
        }
    } 

    function register_shortcode(){
        add_shortcode('post-voting', 'voting_ui' );
    }

    //hook it into wordpress on it
    add_action('init', 'register_shortcode');

    /* usage:  [post-voting]  in the content section*/

?>