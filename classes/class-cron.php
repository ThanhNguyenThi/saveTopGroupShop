<?php
/**
 * Cron class
 *
 * @package groupshops
 */


class TGS_Cron
{


	/**
	 * Constructor
	 */
	function __construct()
	{
		add_filter( 'cron_schedules', array( 'TGS_Cron', 'custom_cron_intervals' ) );
		add_action( TGS_Vendors::$id . '_options_updated', array( 'TGS_Cron', 'check_schedule' ) );
		add_filter( TGS_Vendors::$id . '_options_on_update', array( 'TGS_Cron', 'check_schedule_now' ) );
	}


	/**
	 * Re-add cron schedule when the settings have been updated
	 *
	 * @param         array
	 * @param unknown $options
	 */
	public static function check_schedule( $options )
	{
		$old_interval = wp_get_schedule( 'pv_schedule_mass_payments' );
		$new_interval = $options[ 'schedule' ];
		$instapay     = $options[ 'instapay' ];

		/**
		 * 1. The user actually changed the schedule
		 * 2. Instapay is turned off
		 * 3. Manual was not selected
		 */
		if ( ( $old_interval != $new_interval ) && !$instapay && $new_interval != 'manual' ) {
			TGS_Cron::remove_cron_schedule( $options );
			TGS_Cron::schedule_cron( $new_interval );
		}

		if ( $new_interval == 'manual' || $instapay ) {
			TGS_Cron::remove_cron_schedule( $options );
		}

	}


	/**
	 * Check if the user chose "Now" on the Schedule settings
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public static function check_schedule_now( $options )
	{
		$old_schedule = TGS_Vendors::$pv_options->get_option( 'schedule' );
		$new_schedule = $options[ 'schedule' ];

		if ( $new_schedule == 'now' ) {
			$return                = TGS_Cron::pay_now();
			$options[ 'schedule' ] = $old_schedule;
			TGS_Cron::schedule_cron( $old_schedule );
			add_settings_error( TGS_Vendors::$pv_options->id, 'save_options', $return[ 'message' ], $return[ 'status' ] );
		}

		return $options;
	}


	/**
	 * Pay all outstanding commission using Paypal Mass Pay
	 *
	 * @return array
	 */
	public static function pay_now()
	{
		$mass_pay = new TGS_Mass_Pay;
		$mass_pay = $mass_pay->do_payments();

		$message = !empty( $mass_pay[ 'total' ] )
			? $mass_pay[ 'msg' ] . '<br/>' . sprintf( __( 'Payment total: %s', 'topgroupshops' ), woocommerce_price( $mass_pay[ 'total' ] ) )
			: $mass_pay[ 'msg' ];

		return array(
			'message' => $message,
			'status'  => $mass_pay[ 'status' ]
		);
	}


	/**
	 * Remove the mass payments schedule
	 *
	 * @return bool
	 */
	private static function remove_cron_schedule()
	{
		$timestamp = wp_next_scheduled( 'pv_schedule_mass_payments' );

		return wp_unschedule_event( $timestamp, 'pv_schedule_mass_payments' );
	}


	/**
	 * Schedule a cron event on a specified interval
	 *
	 * @param string $interval
	 *
	 * @return bool
	 */
	public static function schedule_cron( $interval )
	{
		// Scheduled event
		add_action( 'pv_schedule_mass_payments', array( 'TGS_Cron', 'pay_now' ) );

		// Schedule the event
		if ( !wp_next_scheduled( 'pv_schedule_mass_payments' ) ) {
			wp_schedule_event( time(), $interval, 'pv_schedule_mass_payments' );

			return true;
		}

		return false;
	}


	/**
	 * Add new schedule intervals to WP
	 *
	 * Weekly
	 * Biweekly
	 * Monthly
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public static function custom_cron_intervals( $schedules )
	{
		
		$schedules[ 'daily' ] = array(
			'interval' => 86400,
			'display'  => __( 'Once Daily' )
		);

		$schedules[ 'weekly' ] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly' )
		);

		$schedules[ 'biweekly' ] = array(
			'interval' => 1209600,
			'display'  => __( 'Once every two weeks' )
		);

		$schedules[ 'monthly' ] = array(
			'interval' => 2635200,
			'display'  => __( 'Once a month' )
		);

		return $schedules;
	}


}
