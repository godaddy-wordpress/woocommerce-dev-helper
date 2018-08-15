"use strict"

###*
# WooCommerce Dev Helper Memberships Bulk Generator scripts.
#
# @since 1.0.0
###
jQuery( document ).ready ( $ ) ->


	# start a bulk generation process
	$( '#generate-memberships' ).on 'click', ( e ) ->
		e.preventDefault()


	# start a bulk destruction process
	$( '#destroy-memberships' ).on 'click', ( e ) ->
		e.preventDefault()
