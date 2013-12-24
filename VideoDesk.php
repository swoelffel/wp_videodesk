<?php
	/*
	Plugin Name: VideoDesk Integration
	Description: Add the VideoDesk script
	Author: JLA (Castelis)
	Version: 1.0
	Author URI: http://openboutique.fr
	*/

	if(!defined('ABSPATH'))
		exit;

	class OB_VideoDesk
	{
		private $videodesk_woocommerce;

		/* Constructeur */
		public function __construct()
		{
			load_plugin_textdomain('videodesk_integration', false, dirname(plugin_basename(__FILE__)).'/lang/');
			include_once( ABSPATH.'wp-admin/includes/plugin.php' );
			if( is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
			{
				include_once( plugin_dir_path( __FILE__ )."VideoDesk_WooCommerce.php" );
				$this->videodesk_woocommerce = new OB_VideoDesk_WooCommerce($this);
			}

			add_action('admin_menu', 					array($this, 'action_admin_menu') );
			add_action('admin_enqueue_scripts', 		array($this, 'action_admin_enqueue_scripts') );
			add_action('admin_init', 					array($this, 'action_admin_init') );

			add_action('wp_head', 						array($this, 'action_wp_head') );
		}
		/* Constructeur */


		/* Installation, Désinstallation, Update */
		public function activate()
		{
			update_option('videodesk_config', $this->get_default_config());
		}

		public function desactivate()
		{
			delete_option('videodesk_config');
		}

		public function update()
		{
			include_once( plugin_dir_path(__FILE__)."VideoDesk_WooCommerce.php" );
			$this->videodesk_woocommerce = new OB_VideoDesk_WooCommerce($this);

			$this->activate();
		}
		/* Installation, Désinstallation, Update */


		/* Actions HOOK */
		public function action_admin_menu()
		{
			add_menu_page( __('VideoDesk Configuration', 'videodesk_integration', 'videodesk_integration'), 'VideoDesk', 'list_users', 'videodesk', array($this, 'callback_config_page'), plugins_url('/img/logo.gif', __FILE__), '58.9000');
		}

		public function action_admin_enqueue_scripts()
		{
			wp_register_style( 'videodesk_admin', plugins_url('/css/videodesk.css', __FILE__) );
			wp_enqueue_style('videodesk_admin');
		}

		public function action_admin_init()
		{
			register_setting('videodesk_config_options_group', 'videodesk_config', array($this, 'callback_save_config'));

			add_settings_section('setting_section_id', __('Configuration de VideoDesk', 'videodesk_integration'), '', 'videodesk_config');

			add_settings_field('videodesk_uid', __('UID', 'videodesk_integration'), array($this, 'callback_config_uid'), 'videodesk_config', 'setting_section_id');
			add_settings_field('videodesk_active', __('Affichage', 'videodesk_integration'), array($this, 'callback_config_active'), 'videodesk_config', 'setting_section_id');
			add_settings_field('videodesk_affichage', __('Périmètre d\'affichage', 'videodesk_integration'), array($this, 'callback_config_display'), 'videodesk_config', 'setting_section_id');

			if( class_exists("Woocommerce") )
			{
				$title = __('Affichage WooCommerce', 'videodesk_integration');
				if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
					$title .= '<br/><br/><i>'.__('Exclusivement sur', 'videodesk_integration').' <a href="http://www.openboutique.fr">OpenBoutique</a>.</i>';
				add_settings_field('videodesk_woocommerce', $title, array($this, 'callback_config_woocommerce'), 'videodesk_config', 'setting_section_id');
			}
		}

		public function action_wp_head()
		{
			if( !$this->is_plugin_visible() )
				return;

			$s = '';
			if( isset($_SERVER['HTTPS']) )
				$s = 's';
			echo "
				<script type='text/javascript' async='' src='http".$s."://module-videodesk.com/js/videodesk.js'></script>
				<script type='text/javascript'>
					var _videodesk = _videodesk || {};
					_videodesk['uid'] = '".$this->get_config('uid')."';
					_videodesk['lang'] = 'fr';
				</script>";
		}
		/* Actions HOOK */


		/* Callbacks */
		public function callback_config_page()
		{
			echo "
				<div class='wrap'>
					".screen_icon()."<h2>".__('VideoDesk Configuration', 'videodesk_integration')."</h2>
					<form method='post' action='options.php'>";
						settings_fields('videodesk_config_options_group');   
						do_settings_sections('videodesk_config');
						submit_button(); 
            echo "	</form>
				</div>";
		}

		public function callback_config_uid()
		{
			echo "<input type='text' id='videodesk_config' name='videodesk_config[uid]' value='".$this->get_config('uid')."' />";
		}

		public function callback_config_active()
		{
			/* Enabled/Disabled */
			echo "
				<img src='".plugins_url('/img/displayed.png', __FILE__)."'/><input type='radio' name='videodesk_config[active]' value='1' ";
				if( $this->get_config('active') )
					echo "checked";
				echo "																										> <span>".__('Activé', 'videodesk_integration')."</span><br/>
				<img src='".plugins_url('/img/not-displayed.png', __FILE__)."'/><input type='radio' name='videodesk_config[active]' value='0' ";
				if( !$this->get_config('active') )
					echo "checked";
				echo "																											> <span>".__('Désactivé', 'videodesk_integration')."</span>
			";
			/* Enabled/Disabled */


			/* Time check */
			echo "<br/><input type='checkbox' name='videodesk_config[time]' value='1'";
			if( $this->get_config('time') > 0 )
				echo " checked";
			echo "><span> ".__('Si l\'utilisateur a passé plus de', 'videodesk_integration')." </span><input type='text' name='videodesk_config[time_val]' value='".$this->get_config('time')."'";
			echo "><span> ".__('minutes sur la boutique', 'videodesk_integration')."</span>";
			/* Time check */
		}

		public function callback_config_display()
		{
			echo "
				<input type='radio' name='videodesk_config[affiche_type]' value='1' ";
				if( $this->get_config('affichage', 'type') )
					echo "checked";
				echo "																> <span>".__('Afficher sur toutes les pages', 'videodesk_integration')."</span><br/>
				<input type='radio' name='videodesk_config[affiche_type]' value='0' ";
				if( !$this->get_config('affichage', 'type') )
					echo "checked";
				echo "																> <span>".__('Afficher sur les pages suivantes', 'videodesk_integration')." : </span>
			";
			
			$list_pages = get_pages();
			$count = 0;

			echo "<table>";
			foreach( $list_pages as $page )
			{
				$count++;
				if( $count == 1 )
					echo "<tr>";

				echo "<td class='configvideodesk'>
						<input type='checkbox' name='videodesk_config[affiche_page_".$page->ID."]' value='".$page->ID."'";
				if( $this->is_page_allowed($page->ID) )
					echo " checked";
				echo "> ".$page->post_title."</td>";

				if( $count == 4 )
				{
					echo "</tr>";
					$count = 0;
				}
			}
			echo "</table>";
		}

		public function callback_config_woocommerce()
		{
			if( !is_plugin_active('woocommerce/woocommerce.php') )
				return;

			/* Règle d'exécution */
			echo "
				<span>".__('Règle d\'exécution', 'videodesk_integration')." : </span>
				<select name='videodesk_config[woocommerce_condition_type]'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " disabled";
			echo 	"><option";
			if( $this->get_config('conditions', 'type') == __('OU', 'videodesk_integration') )
				echo " selected";
			echo 	">".__('OU', 'videodesk_integration')."
					<option";
			if( $this->get_config('conditions', 'type') == __('ET', 'videodesk_integration') )
				echo " selected";
			echo 	">".__('ET', 'videodesk_integration')."
				</select>";
			/* Règle d'exécution */

			/* Montant Panier */
			echo "<br/><input type='checkbox' name='videodesk_config[woocommerce_montant_panier]' value='1'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " disabled";
			else if( $this->get_config('conditions', 'montant_panier') > 0 )
				echo " checked";
			echo "><span> ".__('Si le montant du panier dépasse', 'videodesk_integration')." </span><input type='text' name='videodesk_config[woocommerce_montant_panier_val]' value='".$this->get_config('conditions', 'montant_panier')."'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " readonly";
			echo "><span> ".get_woocommerce_currency_symbol()."</span>";
			/* Montant Panier */

			/* Montant Produit */
			echo "<br/><input type='checkbox' name='videodesk_config[woocommerce_montant_produit]' value='1'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " disabled";
			else if( $this->get_config('conditions', 'montant_produit') > 0 )
				echo " checked";
			echo " ><span> ".__('Si le montant d\'un produit du panier dépasse', 'videodesk_integration')." </span><input type='text' name='videodesk_config[woocommerce_montant_produit_val]' value='".$this->get_config('conditions', 'montant_produit')."'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " readonly";
			echo " ><span> ".get_woocommerce_currency_symbol()."</span>";
			/* Montant Produit */

			/* Quantité Produit */
			echo "<br/><input type='checkbox' name='videodesk_config[woocommerce_quantite_produit]' value='1'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " disabled";
			else if( $this->get_config('conditions', 'quantite_produit') > 0 )
				echo " checked";
			echo " ><span> ".__('Si le panier contient au moins', 'videodesk_integration')." </span><input type='text' name='videodesk_config[woocommerce_quantite_produit_val]' value='".$this->get_config('conditions', 'quantite_produit')."'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " readonly";
			echo " ><span> ".__('produits', 'videodesk_integration')."</span>";
			/* Quantité Produit */

			/* Utilisateur connecté */
			echo "<br/><input type='checkbox' name='videodesk_config[woocommerce_utilisateur_connecte]' value='1'";
			if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				echo " disabled";
			else if( $this->get_config('conditions', 'est_connecte') == 1 )
				echo " checked";
			echo " ><span> ".__('Si l\'utilisateur est connecté', 'videodesk_integration')."</span>";
			/* Utilisateur connecté */


			/* Catégorie courrante */
			$args = array( 'taxonomy' => 'product_cat', 'hide_empty' => false );
			$product_categories = get_terms('product_cat', $args);
			if( sizeof($product_categories) != 0 )
			{
				$count = 0;

				echo "<br/><span> ".__('Si la catégorie courrante est', 'videodesk_integration')." :</span><table>";
				foreach( $product_categories as $category )
				{
					$count++;
					if( $count == 1 )
						echo "<tr>";

					echo "<td class='configvideodesk'>
								<input type='checkbox' name='videodesk_config[woocommerce_affiche_category_".$category->term_id."]' value='".$category->name."'";
					if( !is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
						echo " disabled";
					else if( $this->videodesk_woocommerce->is_category_allowed($category->term_id) )
						echo " checked";
					echo "> ".$category->name."</td>";

					if( $count == 4 )
					{
						echo "</tr>";
						$count = 0;
					}
				}
				echo "</table>";
			} else {
				echo "<br/><span><i>".__('Vous n\'avez actuellement crée aucune catégorie de produit. La limitation par catégorie n\'est donc pas disponible', 'videodesk_integration').".</i></span>";
			}
			/* Catégorie courrante */
		}

		public function callback_save_config($input)
		{
			$new_input = $this->get_default_config();

			if( isset( $input['uid'] ) )
				$new_input['uid'] = sanitize_text_field( $input['uid'] );

			if( isset( $input['active'] ) && $input['active'] == '0' )
				$new_input['active'] = 0;

			if( isset( $input['time'] ) && $input['time'] == 1 && isset($input['time_val']) && (int)$input['time_val'] > 0 )
				$new_input['time'] = $input['time_val'];

			$list_pages = get_pages();
			foreach( $list_pages as $page )
			{
				if( isset($input['affiche_page_'.$page->ID]) )
				{
					$new_input['affichage']['list_pages_allowed'][$page->ID] = 1;
					$new_input['affichage']['type'] = 0;
				}
			}

			if( is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				$new_input = array_merge( $new_input, $this->videodesk_woocommerce->callback_save_config($input) );

			return $new_input;
		}
		/* Callbacks */


		/* Configurations */
		public function get_default_config()
		{
			$array =
				array(
					"uid" 		=> "0",
					"active" 	=> "1",
					"time" 		=> "0",
					"affichage"	=> array(
						"type"				=> "1",
						"list_pages_allowed"=> array()
					)
				);

			if( !empty($this->videodesk_woocommerce) )
				$array = array_merge( $array, $this->videodesk_woocommerce->get_default_config() );

			return $array;
		}

		public function get_config($param = '', $param2 = '', $param3 = '')
		{
			switch( $param )
			{
				case 'uid':
				case 'time':
					return $this->get_config()[$param];
				case 'active':
					return (int)$this->get_config()[$param];
				case 'affichage':
					switch( $param2 )
					{
						case 'type':
						case 'list_pages_allowed':
							return $this->get_config()[$param][$param2];
						default:
							return 0;
					}
				case 'conditions':
					if( is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
						return $this->videodesk_woocommerce->get_config($param, $param2, $param3);
					return 0;
				default:
					return get_option('videodesk_config');
			}
		}
		/* Configurations */


		/* Autres */
		public function is_page_allowed($id)
		{
			$list = $this->get_config('affichage', 'list_pages_allowed');
			if( isset($list[$id]) )
				return true;
			return false;
		}

		public function get_user_time()
		{
			if( session_status() !== PHP_SESSION_ACTIVE )
				session_start();

			if( !isset($_SESSION['user_time']) )
				$_SESSION['user_time'] = time();

			$time = (time() - $_SESSION['user_time']) / 60;
			return round( $time, 0, PHP_ROUND_HALF_UP);
		}

		public function is_plugin_visible()
		{
			if( !$this->get_config() )
				return false;

			if( $this->get_config('uid') == "0" )
				return false;

			if( $this->get_config('time') > 0 && $this->get_config('time') > $this->get_user_time() )
				return false;

			if( !$this->get_config('active') )
				return false;

			if( !$this->get_config('affichage', 'type') && !$this->is_page_allowed(get_the_ID()) )
				return false;

			if( is_plugin_active('VideoDesk/VideoDesk_WooCommerce.php') )
				if( !$this->videodesk_woocommerce->is_plugin_visible() )
					return false;

			return true;
		}
		/* Autres */
	}

	$videodesk = new OB_VideoDesk();
	register_activation_hook(__FILE__, 		array($videodesk, 'activate'));
	register_deactivation_hook(__FILE__, 	array($videodesk, 'desactivate'));
?>
