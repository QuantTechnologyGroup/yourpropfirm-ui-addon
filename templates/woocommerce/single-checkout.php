<?php

$dark_logo  = yourpropfirm_get_dashboard_asset_url( 'logoUrl', 'dark' );
$light_logo = yourpropfirm_get_dashboard_asset_url( 'logoUrl', 'light' );
// Only treat the dashboard logo as usable when it's an actual image URL. When a
// tenant has no logo configured, the helper returns the bare dashboard domain
// (no image path) — guard against that so the FUNDEDBIT fallback logo shows
// instead of a broken <img>. Production logos end in .png/.svg and still pass.
$ypf_is_image_url = static function ( $url ) {
	return is_string( $url ) && preg_match( '/\.(png|jpe?g|svg|webp|gif|avif)(\?.*)?$/i', $url );
};
if ( ! $ypf_is_image_url( $dark_logo ) || ! $ypf_is_image_url( $light_logo ) ) {
	$dark_logo  = '';
	$light_logo = '';
}
$theme_class = yourpropfirm_detect_theme_mode();
?>

<!doctype html>
<html <?php language_attributes(); ?> class="<?= $theme_class; ?>">

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<script>
		// Dark mode detection and initialization
		(function () {
			const getStoredTheme = () => localStorage.getItem('theme');
			const setStoredTheme = (theme) => localStorage.setItem('theme', theme);
			const getPreferredTheme = () => {
				const storedTheme = getStoredTheme();
				if (storedTheme) {
					return storedTheme;
				}
				return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
			};

			const theme = getPreferredTheme();
			if (theme === 'dark') {
				document.documentElement.classList.remove('light');
				document.documentElement.classList.add('dark');
			} else {
				document.documentElement.classList.remove('dark');
				document.documentElement.classList.add('light');
			}
		})();

		// Iframe detection and initialization
		// This runs immediately before body is rendered to prevent flash
		(function () {
			// Check if page is loaded inside an iframe
			if (window.self !== window.top) {
				// Set global flag for other scripts to use
				window.isInIframeMode = true;
			} else {
				window.isInIframeMode = false;
			}
		})();
	</script>
	<?php wp_head(); ?>
</head>

<body <?php body_class( 'checkout-body' ); ?>>

	<?php wp_body_open(); ?>

	<script>
		// Add iframe-mode class to body if in iframe
		// This runs immediately after body is available
		console.log({
			'isInIframeMode': window.isInIframeMode
		})
		if (window.isInIframeMode) {
			document.body.classList.add('iframe-mode');
		}
	</script>



	<div class="checkout-container">

		<!-- Hide in iframe mode -->
		<div class="tw-relative lg:tw-max-w-[1132px] tw-mx-auto checkout-header-container">
			<!-- Dark Mode Toggle Button -->
			<div class="dark-mode-toggle-container">

				<?php
				yourpropfirm_language_switcher( [
					"dropdown" => true,
					"hide_if_empty" => false
				] );
				?>

				<!-- Dark Mode Toggle -->
				<button id="theme-toggle" class="dark-mode-toggle" aria-label="Toggle dark mode">
					<!-- Sun Icon (Light Mode) -->
					<svg width="16" height="16" viewBox="0 0 16 16" id="sun-icon" class="theme-toggle-icon sun-icon"
						fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_2096_1133)">
							<path
								d="M8.00016 1.33203V2.66536M8.00016 13.332V14.6654M3.28687 3.28532L4.22687 4.22532M11.7735 11.772L12.7135 12.712M1.3335 7.9987H2.66683M13.3335 7.9987H14.6668M4.22687 11.772L3.28687 12.712M12.7135 3.28532L11.7735 4.22532M10.6668 7.9987C10.6668 9.47146 9.47292 10.6654 8.00016 10.6654C6.5274 10.6654 5.3335 9.47146 5.3335 7.9987C5.3335 6.52594 6.5274 5.33203 8.00016 5.33203C9.47292 5.33203 10.6668 6.52594 10.6668 7.9987Z"
								stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</g>
						<defs>
							<clipPath id="clip0_2096_1133">
								<rect width="16" height="16" fill="white" />
							</clipPath>
						</defs>
					</svg>

					<!-- Moon Icon (Dark Mode) -->
					<svg id="moon-icon" class="theme-toggle-icon moon-icon" width="16" height="16" viewBox="0 0 16 16"
						fill="none" xmlns="http://www.w3.org/2000/svg">
						<g clip-path="url(#clip0_2096_18225)">
							<path
								d="M14.6668 7.9987C14.6668 11.6806 11.6821 14.6654 8.00016 14.6654C4.31826 14.6654 1.3335 11.6806 1.3335 7.9987C1.3335 4.3168 4.31826 1.33203 8.00016 1.33203M14.6668 7.9987C14.6668 4.3168 11.6821 1.33203 8.00016 1.33203M14.6668 7.9987C13.7828 8.88275 12.5838 9.37941 11.3335 9.37941C10.0833 9.37941 8.88423 8.88275 8.00018 7.9987C7.11612 7.11464 6.61947 5.91561 6.61947 4.66536C6.61947 3.41512 7.11611 2.21609 8.00016 1.33203"
								stroke="#666666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</g>
						<defs>
							<clipPath id="clip0_2096_18225">
								<rect width="16" height="16" fill="white" />
							</clipPath>
						</defs>
					</svg>
				</button>
			</div>

			<?php // display logo ?>
			<div class="checkout-logo">

				<?php
				$logo = get_custom_logo();
				if ( $dark_logo && $light_logo ) {
					?>
					<img src="<?php echo esc_url( $dark_logo ); ?>" alt="Logo"
						class="tw-hidden dark:tw-block tw-w-full tw-h-auto" />
					<img src="<?php echo esc_url( $light_logo ); ?>" alt="Logo"
						class="tw-block dark:tw-hidden tw-w-full tw-h-auto" />
					<?php
				} elseif ( $logo ) {
					echo $logo;
				} else {
					// FUNDEDBIT brand logo.
					?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ypf-brand-logo" aria-label="FundedBit">
						<img src="<?php echo esc_url( YOURPROPFIRM_UI_ADDON_URL . 'assets/images/fundedbit.png' ); ?>"
							alt="FundedBit" width="176" height="29" />
					</a><?php
				}
				?>
			</div>
		</div>
		<!-- Hide in iframe mode -->

		<div class="yourpropfirm-checkout woocommerce-override tw-justify-center tw-flex">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
			endif;
			?>

		</div>

		<!-- Hide in iframe mode -->
		<div class="background-placement background-bottom-left">
			<svg width="737" height="927" viewBox="0 0 737 927" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g filter="url(#filter0_f_250_10536)">
					<path
						d="M117.402 416.77C117.402 346.77 70.7349 285.603 47.4016 263.77C-62.0984 141.27 274.902 207.27 462.402 359.77C649.902 512.27 444.902 613.27 274.902 740.27C104.902 867.27 117.402 504.27 117.402 416.77Z"
						fill="url(#paint0_linear_250_10536)" fill-opacity="0.5" />
				</g>
				<defs>
					<filter id="filter0_f_250_10536" x="-174" y="0" width="910.974" height="966.945"
						filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
						<feFlood flood-opacity="0" result="BackgroundImageFix" />
						<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
						<feGaussianBlur stdDeviation="100" result="effect1_foregroundBlur_250_10536" />
					</filter>
					<linearGradient id="paint0_linear_250_10536" x1="281.487" y1="200" x2="281.487" y2="766.944"
						gradientUnits="userSpaceOnUse">
						<stop style="stop-color: var(--yourpropfirm-primary)" />
						<stop offset="1" style="stop-color: var(--yourpropfirm-primary); stop-opacity: 0.5" />
					</linearGradient>
				</defs>
			</svg>


		</div>
		<div class="background-placement background-top-right">
			<svg width="818" height="932" viewBox="0 0 818 932" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g filter="url(#filter0_f_250_10535)">
					<path
						d="M541.532 308.009C611.292 302.222 668.392 250.658 688.221 225.6C801.25 106.348 763.335 447.651 626.857 647.116C490.379 846.581 372.778 650.632 232.159 491.713C91.5404 332.793 454.331 315.242 541.532 308.009Z"
						fill="url(#paint0_linear_250_10535)" fill-opacity="0.5" />
				</g>
				<defs>
					<filter id="filter0_f_250_10535" x="0.132568" y="0.933594" width="954.625" height="930.617"
						filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
						<feFlood flood-opacity="0" result="BackgroundImageFix" />
						<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
						<feGaussianBlur stdDeviation="100" result="effect1_foregroundBlur_250_10535" />
					</filter>
					<linearGradient id="paint0_linear_250_10535" x1="746.089" y1="155.076" x2="287.912" y2="631.583"
						gradientUnits="userSpaceOnUse">
						<stop style="stop-color: var(--yourpropfirm-primary)" />
						<stop offset="1" style="stop-color: var(--yourpropfirm-primary); stop-opacity: 0.5" />
					</linearGradient>
				</defs>
			</svg>
		</div>
		<!-- Hide in iframe mode -->
	</div>

	<?php wp_footer(); ?>
</body>

</html>