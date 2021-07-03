<?php
/**
 * Core theme file.
 *
 * @package  Headless
 * @author   slushman
 * @license  GPL-2.0+
 * @link     https://developer.wordpress.org/themes/basics/theme-functions/
 */
add_action( 'after_setup_theme', 'headless_theme_setup' );
add_action( 'after_setup_theme', 'headless_text_domain' );
add_action( 'after_setup_theme', 'headless_register_menus' );

add_filter( 'use_default_gallery_style', '__return_false' );
add_filter( 'the_content', 'headless_remove_brs_from_galleries', 11, 2 );


/**
 * Registers Menus
 *
 * @hooked 		after_setup_theme
 * @since 		1.0.0
 */
function headless_register_menus() {

	register_nav_menus( array(
		'main' 		=> esc_html__( 'Main', 'slushless' ),
		'social' 	=> esc_html__( 'Social', 'slushless' ),
		'footer' 	=> esc_html__( 'Footer', 'slushless' )
	) );

} // headless_register_menus()

/**
 * Removes the random br tags from WordPress galleries.
 * 
 * @hooked 		the_content
 * @since 		1.0.2
 * @param 		mixed 		$output 		The post content.
 * @param 		mixed 						The modified post content.
 */
function headless_remove_brs_from_galleries( $output ) {

	return preg_replace( '/\<br[^\>]*\>/', '', $output );

} // headless_remove_brs_from_galleries()

/*
 * Make theme available for translation.
 * Translations can be filed in the /languages/ directory.
 *
 * @hooked 		after_setup_theme
 * @since 		1.0.0
 */
function headless_text_domain() {

	load_theme_textdomain( 'headless', get_template_directory() . '/languages' );

} // headless_text_domain()

/**
 * Sets up basic items needed for the theme to work.
 * 
 * @hooked 		after_setup_theme
 * @since 		1.0.0
 */
function headless_theme_setup() {

	add_theme_support( 'post-thumbnails' );

} // headless_theme_setup()


// CREATE POSTS 
register_post_type('plant', [
  'label' => 'Plant', 
  'public' => true,
  'menu_position' => 3,
  'menu_icon' => 'dashicons-palmtree',
  'support' => ['title', 'thumbnail']
]);

register_post_type('user', [
  'label' => 'User', 
  'public' => true,
  'menu_position' => 2,
  'menu_icon' => 'dashicons-admin-users',
  'support' => ['title', 'thumbnail']
]);

register_post_type('verifystack', [
  'label' => 'VerifyStack', 
  'public' => true,
  'menu_position' => 4,
  'menu_icon' => 'dashicons-clock',
  'support' => ['title', 'thumbnail']
]);

register_meta( 'plant', '<plant>', array( 'show_in_rest' => true ) );

// MY CODE
add_action('rest_api_init', function() {
	function buildPlantList($list){		
		$strucuredList = [];

		foreach ($list as $plant){
			$newPlant = get_fields($plant->ID);
			$newPlant['id'] =  $plant->ID;
			$newPlant['likes'] = (int)$newPlant['likes'];
			
			array_push($strucuredList, $newPlant);
		};

		return $strucuredList;
	}

	function getUserFromKey($key){		
		$users = get_posts([
			'numberposts' => -1,
			'post_type' => 'user'
		]);

		foreach($users as $user){
			$userFields = get_fields($user -> ID);

			if ((int)$userFields['googlekey'] === $key) {
							
				$userFields['id'] = $user -> ID;
				return $userFields;
			};
		}
		return false;
	}

	function stringArrayToPostsArray($array){		
		$idArray = explode(',', $array);
		$structuredArray = array();
		
		foreach ($idArray as $id){
			if ($id !== ""){
				$list = get_fields((int)$id);
				$list['id'] = $id;

				array_push($structuredArray, $list);
			}
		};
		return $structuredArray; 
	}

	function buildFavArray($userFields){		
		// Build Favorites Array
		$userFields['favorites'] = explode(',', $userFields['favorites']);
		$favoriteList = array();

		foreach ($userFields['favorites'] as $favPlant){
			if($favPlant !== ''){
				array_push($favoriteList, (int)$favPlant);												
			};
		};
		$userFields['favorites'] = $favoriteList;

		return $userFields;
	}

	// Get One User from id
	register_rest_route('api/', 'user/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$userId = (int)$request->get_param('id');

			$user = get_fields($userId);

			// Build publicaitons list
			$user['publications'] = stringArrayToPostsArray($user['publications']);
			
			// Build favorite list
			$user['favorites'] = stringArrayToPostsArray($user['favorites']);

			$user['id'] = $userId;
			return $user;			
		}
	]);

	register_rest_route('api/', 'refreshuser/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$userId = (int)$request->get_param('id');

			$user = get_fields($userId);

			$user = buildFavArray($user);			

			$user['id'] = $userId;
			return $user;			
		}
	]);

	// Get One Plant and it Creator from plant ID
	register_rest_route('api/', 'plant/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$postID = (int)$request->get_param('id');

			$plant = get_fields($postID);
			$plant['id'] = $postID;

			$creator = getUserFromKey((int)$plant['creator']);
			
			$plant['user'] = $creator;

			return $plant;
		}
	]);
	
	// Get Every Plants
	register_rest_route('api/', 'plant', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$plants =  get_posts([
        'numberposts' => -1,
        'post_type' => 'plant',
			]);
			
			return buildPlantList($plants);
		}
	]);

	register_rest_route('api/', 'verifystack', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$stack =  get_posts([
        'numberposts' => -1,
        'post_type' => 'verifystack',
			]);
			
			return buildPlantList($stack);
		}
	]);

	register_rest_route('api/', 'user', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$users =  get_posts([
        'numberposts' => -1,
        'post_type' => 'user',
			]);
			
			$listBuilded = array();
			foreach ($users as $user){
				$part = get_fields($user -> ID);
				$part['id'] = $user -> ID;

				array_push($listBuilded, $part);
			};
			
			return $listBuilded;
		}
	]);

	// Create / Login - User
	register_rest_route('api/', 'user/(?P<googlekey>\d+)/(?P<username>\w+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$googleKey = (int)$request->get_param('googlekey');
			$username = (string)$request->get_param('username');
			$imageUrl = (string)$request->get_param('imageurl');
			
			$userFields = getUserFromKey($googleKey);
			if($userFields){
				return buildFavArray($userFields);			
			};

			// User Not Found -> Create New User
			// Create Post
			$newUserId = wp_insert_post([	
				'post_title' => $username,
				'post_status' => 'publish',
				'post_author' => $username,
				'post_type' => 'user'
			]);

			// PopulateFields
			update_field('googlekey', $googleKey, $newUserId);
			update_field('username', $username, $newUserId);
			update_field('profileimage', $imageUrl, $newUserId);
			update_field('favorites', '', $newUserId);
			update_field('admin', 0, $newUserId);
			update_field('publications', '', $newUserId);

			$newUser = get_fields($newUserId);
			$newUser['id'] = $newUserId;
			return $newUser;
		}
	]);
	
	// Add a Plant to Favorites
	register_rest_route('api/', 'addfav/(?P<userid>\d+)/(?P<plantid>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$userId = (int)$request->get_param('userid');
			$plantId = (int)$request->get_param('plantid');

			// Add plant ID to User Favorite list
			$userFavoritesList = explode(',', get_field('favorites', $userId));
			array_push($userFavoritesList, $plantId);
			$userFavoritesList = implode(',', $userFavoritesList);
			update_field('favorites', $userFavoritesList, $userId);
			
			// Increment Plant Favorite Count
			$plantFavoritesCount = (int)get_field('likes', $plantId);
			update_field('likes', $plantFavoritesCount + 1, $plantId);

			return true;
		}
	]);

	// Remove a Plant to Favorites
	register_rest_route('api/', 'delfav/(?P<userid>\d+)/(?P<plantid>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$userId = (int)$request->get_param('userid');
			$plantId = (int)$request->get_param('plantid');
			
			// Add plant ID to User Favorite list
			$userFavoritesList = explode(',', get_field('favorites', $userId));

			if (($key = array_search($plantId, $userFavoritesList)) !== false) {
				unset($userFavoritesList[$key]);
			}
			$userFavoritesList = implode(',', $userFavoritesList);
			update_field('favorites', $userFavoritesList, $userId);
			
			// Decrement Plant Favorite Count
			$plantFavoritesCount = (int)get_field('likes', $plantId);
			update_field('likes', $plantFavoritesCount - 1, $plantId);

			return true;
		}
	]);

	// Create a Plant
	register_rest_route('api/', 'newplant', [
		'methods' => 'POST',
		'callback' => function (WP_REST_Request $request){
			$name = $request['name'];
			$description = $request['description'];
			$matureHeight = $request['matureheight'];
			$requiredHeat = $request['requiredheat'];
			$difficulty = $request['difficulty'];
			$luminosity = $request['luminosity'];
			$fogging = $request['fogging'];
			$image = $request['image'];
			$creator = $request['creator'];

			$newPlantId = wp_insert_post([	
				'post_title' => $name,
				'post_status' => 'publish',
				'post_author' => $creator,
				'post_type' => 'verifystack'
			]);

			// PopulateFields
			update_field('name', $name, $newPlantId);
			update_field('description', $description, $newPlantId);
			update_field('matureheight', $matureHeight, $newPlantId);
			update_field('requiredheat', $requiredHeat, $newPlantId);
			update_field('difficulty', $difficulty, $newPlantId);
			update_field('luminosity', $luminosity, $newPlantId);
			update_field('fogging', $fogging, $newPlantId);
			update_field('image', $image, $newPlantId);
			update_field('creator', $creator, $newPlantId);
			update_field('likes', 0, $newPlantId);

			return true;
		}
	]);
	
	register_rest_route('api/', 'delete/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$postID = (int)$request->get_param('id');

			wp_trash_post($postID);
			return true;
		}
	]);

	register_rest_route('api/', 'transferplant/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => function (WP_REST_Request $request){
			$postID = (int)$request->get_param('id');

			$my_post = array(
				'ID' => $postID,
				'post_type' => 'plant'
			);
			wp_update_post( $my_post );

			// Add plant ID to User publications list
			$user = getUserFromKey((int)get_field('creator', $postID));
			$userPublicationsList = explode(',', get_field('publications', $user['id']));
			array_push($userPublicationsList, $postID);
			$userPublicationsList = implode(',', $userPublicationsList);
			update_field('publications', $userPublicationsList,$user['id']);
			
			return true;
		}
	]);
});

