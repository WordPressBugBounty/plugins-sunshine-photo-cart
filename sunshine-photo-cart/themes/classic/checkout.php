<?php
defined( 'ABSPATH' ) || exit;

sunshine_get_template( 'header' );

echo '<h1>' . get_the_title( SPC()->get_page( 'checkout' ) ) . '</h1>';

do_action( 'sunshine_before_content' );

do_action( 'sunshine_checkout' );

do_action( 'sunshine_after_content' );

sunshine_get_template( 'footer' );
