<center>
<p>
        <a href="<?php echo $shop_page; ?>" class="button"><?php echo _e( 'View Your Store', 'topgroupshops' ); ?></a>
        <a href="<?php echo $settings_page; ?>" class="button"><?php echo _e( 'Store Settings', 'topgroupshops' ); ?></a>

<?php if ( $can_submit ) { ?>
                <a target="_TOP" href="<?php echo $submit_link; ?>" class="button"><?php echo _e( 'Add New Product', 'topgroupshops' ); ?></a>
                <a target="_TOP" href="<?php echo $edit_link; ?>" class="button"><?php echo _e( 'Edit Products', 'topgroupshops' ); ?></a>
<?php } 
do_action( 'topgroupshops_after_links' );
?>
</center>

<hr>