<?php
/**
 * Animated delivery scene (order tracking page header): a truck loaded with
 * logs driving through the countryside. Colors follow the Customizer palette.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="ps-truck-art" viewBox="0 0 560 340" xmlns="http://www.w3.org/2000/svg" focusable="false">
	<defs>
		<linearGradient id="ps-truck-sky" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" style="stop-color:color-mix(in srgb, var(--ps-accent) 14%, var(--ps-surface))"/>
			<stop offset="1" style="stop-color:var(--ps-surface)"/>
		</linearGradient>
		<clipPath id="ps-truck-clip"><rect width="560" height="340" rx="28"/></clipPath>
	</defs>
	<g clip-path="url(#ps-truck-clip)">
		<rect width="560" height="340" fill="url(#ps-truck-sky)"/>
		<circle class="ps-truck-art__sun" cx="440" cy="84" r="38" style="fill:color-mix(in srgb, var(--ps-accent) 45%, #ffd27a)"/>

		<!-- Far hills (slow) -->
		<g class="ps-truck-art__far">
			<path d="M0 220 C80 170 150 175 230 205 S380 160 460 190 S560 200 620 185 S760 165 840 200 S980 175 1120 205 V340 H0Z" style="fill:color-mix(in srgb, var(--ps-success) 22%, var(--ps-surface))"/>
		</g>

		<!-- Trees (medium speed) -->
		<g class="ps-truck-art__trees" style="fill:color-mix(in srgb, var(--ps-success) 70%, var(--ps-primary))">
			<?php
			foreach ( array( 30, 120, 175, 290, 360, 470, 530, 590, 680, 735, 850, 920, 1030, 1090 ) as $i => $x ) {
				$h = 38 + ( $i * 13 ) % 26;
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- numbers only.
				printf( '<path d="M%1$d %2$d l-%3$d %4$d h%5$d Z"/><rect x="%6$d" y="%7$d" width="4" height="10" rx="1" style="fill:var(--ps-accent)"/>', (int) $x, 232 - $h, (int) ( $h * .36 ), (int) $h, (int) ( $h * .72 ), (int) $x - 2, 232 );
			}
			?>
		</g>

		<!-- Road -->
		<rect y="242" width="560" height="98" style="fill:color-mix(in srgb, var(--ps-primary) 82%, var(--ps-surface))"/>
		<rect y="242" width="560" height="6" style="fill:color-mix(in srgb, var(--ps-primary) 60%, var(--ps-surface))"/>
		<g class="ps-truck-art__dashes" fill="#fff" opacity=".75">
			<?php for ( $x = 0; $x < 1120; $x += 80 ) : ?>
				<rect x="<?php echo (int) $x; ?>" y="292" width="44" height="6" rx="3"/>
			<?php endfor; ?>
		</g>

		<!-- Truck -->
		<g class="ps-truck-art__truck">
			<g class="ps-truck-art__body">
				<!-- smoke -->
				<circle class="ps-truck-art__smoke" cx="128" cy="236" r="7" fill="#fff"/>
				<circle class="ps-truck-art__smoke ps-truck-art__smoke--2" cx="128" cy="236" r="6" fill="#fff"/>
				<!-- trailer bed -->
				<rect x="140" y="214" width="190" height="20" rx="4" style="fill:var(--ps-primary)"/>
				<!-- logs -->
				<g>
					<?php
					$logs = array( array( 168, 202 ), array( 204, 202 ), array( 240, 202 ), array( 276, 202 ), array( 312, 202 ), array( 186, 172 ), array( 222, 172 ), array( 258, 172 ), array( 294, 172 ), array( 204, 142 ), array( 240, 142 ), array( 276, 142 ) );
					foreach ( $logs as $log ) {
						printf(
							'<circle cx="%1$d" cy="%2$d" r="16" style="fill:color-mix(in srgb, var(--ps-accent) 75%%, #6b3d12)"/><circle cx="%1$d" cy="%2$d" r="12" fill="#e7c08a"/><circle cx="%1$d" cy="%2$d" r="7" fill="none" stroke="#c99a5f" stroke-width="1.6"/><circle cx="%1$d" cy="%2$d" r="2.5" fill="#c99a5f"/>',
							(int) $log[0],
							(int) $log[1]
						);
					}
					?>
				</g>
				<!-- stakes -->
				<rect x="146" y="150" width="6" height="66" rx="2" style="fill:var(--ps-primary)"/>
				<rect x="324" y="150" width="6" height="66" rx="2" style="fill:var(--ps-primary)"/>
				<!-- cab -->
				<path d="M336 160 h58 l34 40 v34 h-92 Z" style="fill:var(--ps-accent)"/>
				<path d="M348 170 h40 l24 30 h-64 Z" fill="#dff1ff" opacity=".9"/>
				<rect x="336" y="214" width="92" height="20" rx="4" style="fill:color-mix(in srgb, var(--ps-accent) 70%, black)"/>
				<rect x="420" y="218" width="12" height="8" rx="2" fill="#ffe27a"/>
			</g>
			<!-- wheels -->
			<?php foreach ( array( 178, 292, 392 ) as $cx ) : ?>
				<g class="ps-truck-art__wheel" style="transform-origin:<?php echo (int) $cx; ?>px 244px">
					<circle cx="<?php echo (int) $cx; ?>" cy="244" r="20" fill="#1d1f24"/>
					<circle cx="<?php echo (int) $cx; ?>" cy="244" r="9" fill="#cfd2d8"/>
					<rect x="<?php echo (int) $cx - 2; ?>" y="226" width="4" height="36" rx="2" fill="#cfd2d8" opacity=".7"/>
				</g>
			<?php endforeach; ?>
		</g>
	</g>
</svg>
