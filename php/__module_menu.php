<?php
namespace SIM\TRELLO;
use SIM;

const MODULE_VERSION		= '8.0.1';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_submenu_options', __NAMESPACE__.'\moduleOptions', 10, 3);
function moduleOptions($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();

	$trello	= new Trello();
	
	//Trelle webhook page
	?>	
	<br>
	<label>
		Trello API key
		<input type="text" name="key" value="<?php echo $settings["key"]; ?>" style="width:100%;">
	</label>
	<br>
	<label>
		Trello API token
		<input type="text" name="token" value="<?php echo $settings["token"]; ?>" style="width:100%;">
	</label>
	<br>
	<?php
	if(isset($settings["key"]) && isset($settings["token"]) && !str_contains(SITEURL, 'localhost')){
		?>
		<label>
			Trello board you want listen to
		</label>
		<select name='board'>
			<option value="">---</option>
			<?php
			foreach ($trello->getBoards() as $name=>$id){
				if($settings["board"] == $id){
					$selected = 'selected="selected"';
				}else{
					$selected = '';
				}
				echo "<option value='$id' $selected>$name</option>";
			}
			?>
		</select>
		<?php
	}

	return ob_get_clean();
}

add_filter('sim_module_updated', __NAMESPACE__.'\moduleUpdated', 10, 3);
function moduleUpdated($newOptions, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $newOptions;
	}

	//Trello token has changed
	if($oldOptions['token'] != $newOptions['token']){
		$trello	= new Trello();
		//remove all webhooks from the old token
		$trello->deleteAllWebhooks();

		//Now that the new trello token is set, lets create new webhooks
		$Modules[$moduleSlug]	= $newOptions;

		// Inititate a new trello object with the new token
		$trello = new trello();
		
		//remove all webhooks from the new token
		$trello->deleteAllWebhooks();
		
		//Get the userid belonging to the new token
		$trelloUserId = $trello->getTokenInfo()->id;
		
		//Create a webhook listening to the userid	
		$trello->createWebhook(SITEURL.'/wp-json/'.RESTAPIPREFIX.'/trello', $trelloUserId, "Listens to all actions related to the user with id $trelloUserId");
	}

	return $newOptions;
	
}