<?php  	
	/*
		Preheader
	*/
	function pmpron_manage_sites_preheader()
	{
		if(!is_admin())
		{
			global $post, $current_user;
			if(!empty($post->post_content) && strpos($post->post_content, "[pmpron_manage_sites]") !== false)
			{
				/*
					Preheader operations here.
				*/

				//make sure they have site credits
				global $current_user;
				$credits = $current_user->pmpron_site_credits;
				
				if(empty($credits))
				{
					//redirect to levels
					wp_redirect(pmpro_url("levels"));
					exit;
				}
			}
		}
	}
	add_action("wp", "pmpron_manage_sites_preheader", 1);	
	
	/*
		Shortcode Wrapper
	*/
	function pmpron_manage_sites_shortcode($atts, $content=null, $code="")
	{			
		ob_start();		
		
		global $current_user;			
		
		//adding a site?
		if(!empty($_POST['addsite']))
		{
			$sitename = $_REQUEST['sitename'];
			$sitetitle = $_REQUEST['sitetitle'];
			
			if(!empty($sitename) && !empty($sitetitle))
			{
				if(pmpron_checkSiteName($sitename))
				{
					$blog_id = pmpron_addSite($sitename, $sitetitle);
					if(is_wp_error($blog_id))
					{
						$pmpro_msg = "Error creating site.";
						$pmpro_msgt = "pmpro_error";
					}
					else
					{
						$pmpro_msg = "Your site has been created.";
						$pmpro_msgt = "pmpro_success";
					}
				}
				else
				{
					//error set in checkSiteName					
				}								
			}
			else
			{
				$pmpro_msg = "Please enter a site name and title.";
				$pmpro_msgt = "pmpro_error";
			}			
		}
		else
		{
			//default values for form
			$sitename = "";
			$sitetitle = "";
		}
		
		//show page		
		$blog_ids = pmpron_getBlogsForUser($current_user->ID);	
		?>
		<p>
			You have used <?php echo count($blog_ids);?> of <?php echo intval($current_user->pmpron_site_credits);?> site credits.			
		</p>
		
		<hr />
		
		<?php if($current_user->pmpron_site_credits > count($blog_ids)) { ?>
		<h3>Add a Site</h3>
		<?php if(!empty($pmpro_msg)) { ?>
			<p class="pmpro_message <?php echo $pmpro_msgt;?>"><?php echo $pmpro_msg;?></p>
		<?php } ?>
		<form action="" method="post">
			<p>
				<label for="sitename"><?php _e('Site Name') ?></label>
				<input id="sitename" name="sitename" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitename)); ?>" />				
				<?php
					global $current_site;
					$site_domain = preg_replace( '|^www\.|', '', $current_site->domain );
				
					if ( !is_subdomain_install() )
						$site = $current_site->domain . $current_site->path . __( 'sitename' );
					else
						$site = __( '{site name}' ) . '.' . $site_domain . $current_site->path;

					echo '<div>(<strong>' . sprintf( __('Your address will be %s.'), $site ) . '</strong>) ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed, so choose carefully!' ) . '</div>';						
				?>
			</p>
			<p>
				<label for="sitetitle"><?php _e('Site Title')?></label>
				<input id="sitetitle" name="sitetitle" type="text" class="input" size="30" value="<?php echo esc_attr(stripslashes($sitetitle)); ?>" />
			</p>
			<p>
				<input type="hidden" name="addsite" value="1" />
				<input type="submit" name="submit" value="Add Site" />
			</p>
		</form>
		
		<hr />
		<?php } ?>
		
		<h3>Your Sites</h3>		
		<ul>
			<?php foreach($blog_ids as $blog_id) { ?>
				<li>
					<a href="<?php echo get_site_url($blog_id);?>"><?php echo get_blog_option($blog_id, "blogname");?></a>
					<?php if(get_blog_status($blog_id, "deleted")) { ?>
						(deactivated)
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
		<?php
		$temp_content = ob_get_contents();
		ob_end_clean();
		return $temp_content;			
	}
	add_shortcode("pmpron_manage_sites", "pmpron_manage_sites_shortcode");
	