"use strict"

###*
# WooCommerce Dev Helper Memberships Bulk Generator scripts.
#
# @since 1.0.0
###
jQuery( document ).ready ( $ ) ->


	###*
  # Returns a background job status.
  #
  # @since 1.0.0
  #
  # @param {string} which job to check status update for
  # @param {string} job_id job identifier
  ###
	checkProgress = ( which, jobID ) ->

		if $( 'p.bulk-generation-status .progress' ).length is 0
			$( 'p.bulk-generation-status' ).append( ' <span class="progress"></span>' )

		if 'wc_dev_helper_get_memberships_bulk_generation_status' is which
			security = wc_dev_helper_memberships_bulk_generation.get_memberships_bulk_generation_status_nonce
			success  = wc_dev_helper_memberships_bulk_generation.i18n.generation_success
			error    = wc_dev_helper_memberships_bulk_generation.i18n.generation_error
			progress = wc_dev_helper_memberships_bulk_generation.i18n.generation_in_progress
		else if 'wc_dev_helper_get_memberships_bulk_destruction_status' is which
			security = wc_dev_helper_memberships_bulk_generation.get_memberships_bulk_generation_status_nonce
			success  = wc_dev_helper_memberships_bulk_generation.i18n.destruction_success
			error    = wc_dev_helper_memberships_bulk_generation.i18n.destruction_error
			progress = wc_dev_helper_memberships_bulk_generation.i18n.destruction_in_progress
		else
			console.log( 'Invalid action: ' + which )
			return

		error   = '<span class="error"   style="color: #DC3232;">&#10005;</span> ' + error
		success = '<span class="success" style="color: #008000;">&#10004;</span> ' + success
		data    =
			action   : which
			security : security
			job_id   : jobID

		$.post( wc_dev_helper_memberships_bulk_generation.ajax_url, data )

			.done ( response ) ->

				if response.success

					if response.data.status isnt 'completed'

						processed = if response.data.progress then parseInt( response.data.progress, 10 ) else 0
						total     = if response.data.total then parseInt( response.data.total, 10 ) else 0

						$( 'p.bulk-generation-status .progress' ).html( progress + ' (' + processed + ' / ' + total + ')' )

						return setTimeout( checkProgress( response.data.id ), 100000 )

					else

						$( '#bulk-processing-memberships-spinner' ).removeClass( 'is-active' )
						$( 'p.bulk-generation-status' ).html( success )
						$( '#process-memberships' ).prop( 'disabled', false )

				else

					$( 'p.bulk-generation-status' ).html( error + ' ' + if 'undefined' isnt response.data then response.data else '' )
					$( '#process-memberships' ).prop( 'disabled', false )

					console.log( response.data )

			.fail ( response ) ->

				$( 'p.bulk-generation-status' ).html( error + ' ' + if response.data then response.data else '' )
				$( '#bulk-processing-memberships-spinner' ).removeClass( 'is-active' )
				$( '#process-memberships' ).prop( 'disabled', false )

				console.log( response.data )

	# check status once on page load
	if wc_dev_helper_memberships_bulk_generation.bulk_generation_job_in_progress
		checkProgress( 'wc_dev_helper_get_memberships_bulk_generation_status', wc_dev_helper_memberships_bulk_generation.bulk_generation_job_in_progress )
	if wc_dev_helper_memberships_bulk_generation.bulk_destruction_job_in_progress
		checkProgress( 'wc_dev_helper_get_memberships_bulk_destruction_status', wc_dev_helper_memberships_bulk_generation.bulk_destruction_job_in_progress )


	# start a background process
	$( '#process-memberships' ).on 'click', ( e ) ->
		e.preventDefault()

		# generate memberships
		if $( this ).hasClass( 'generate-memberships' )
			action = 'wc_dev_helper_memberships_bulk_generate'
			which  = 'wc_dev_helper_get_memberships_bulk_generation_status'
			nonce  = wc_dev_helper_memberships_bulk_generation.start_memberships_bulk_generation_nonce
			count  = parseInt( $( '#members-to-generate' ).val(), 10 )
		# destroy memberships
		else if $( this ).hasClass( 'destroy-memberships' )
			action = 'wc_dev_helper_memberships_bulk_destroy'
			which = 'wc_dev_helper_get_memberships_bulk_destruction_status'
			nonce  = wc_dev_helper_memberships_bulk_generation.start_memberships_bulk_destruction_nonce
			count  = 0
		else
			console.log( $( this ) )
			return

		$( this ).prop( 'disabled', true )
		$( '#bulk-processing-memberships-spinner' ).addClass( 'is-active' )

		data =
			action              : action
			security            : nonce
			members_to_generate : count

		$.post wc_dev_helper_memberships_bulk_generation.ajax_url, data, ( response ) ->
			if response.success
				checkProgress( which, if response.data and response.data.id then response.data.id else null )
			else
				$( '#bulk-processing-memberships-spinner' ).removeClass( 'is-active' )
				$( 'p.bulk-generation-status' ).html( if response.data then response.data else '' )
				$( '#process-memberships' ).prop( 'disabled', false )
				console.log( response )
