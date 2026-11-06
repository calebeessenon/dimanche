<?php
/**
 * Abstract still-life artwork shown in the hero until an image is chosen.
 * Colors follow the Customizer palette through CSS variables.
 *
 * @package Premium_Shop
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="ps-hero-art" viewBox="0 0 560 680" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" focusable="false">
	<defs>
		<linearGradient id="ps-art-bg" x1="0" y1="0" x2="0" y2="1">
			<stop offset="0" style="stop-color:var(--ps-soft)"/>
			<stop offset="1" style="stop-color:color-mix(in srgb, var(--ps-accent) 32%, var(--ps-soft))"/>
		</linearGradient>
		<linearGradient id="ps-art-vase" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0" style="stop-color:color-mix(in srgb, var(--ps-accent) 85%, white)"/>
			<stop offset="1" style="stop-color:var(--ps-accent)"/>
		</linearGradient>
		<linearGradient id="ps-art-dark" x1="0" y1="0" x2="1" y2="1">
			<stop offset="0" style="stop-color:color-mix(in srgb, var(--ps-primary) 80%, white)"/>
			<stop offset="1" style="stop-color:var(--ps-primary)"/>
		</linearGradient>
		<radialGradient id="ps-art-sun" cx="0.5" cy="0.5" r="0.5">
			<stop offset="0" stop-color="#fff" stop-opacity=".9"/>
			<stop offset="1" stop-color="#fff" stop-opacity="0"/>
		</radialGradient>
	</defs>
	<rect width="560" height="680" fill="url(#ps-art-bg)"/>
	<circle cx="380" cy="190" r="150" fill="url(#ps-art-sun)"/>
	<circle cx="380" cy="190" r="92" fill="none" style="stroke:var(--ps-accent)" stroke-opacity=".35"/>
	<path d="M0 520 C 140 470, 300 500, 560 455 L560 680 L0 680Z" style="fill:color-mix(in srgb, var(--ps-accent) 18%, var(--ps-surface))"/>
	<!-- plinths -->
	<rect x="70" y="430" width="200" height="190" rx="6" style="fill:var(--ps-surface)"/>
	<rect x="70" y="430" width="200" height="18" style="fill:color-mix(in srgb, var(--ps-border) 70%, white)"/>
	<rect x="300" y="480" width="190" height="140" rx="6" style="fill:color-mix(in srgb, var(--ps-surface) 70%, var(--ps-soft))"/>
	<!-- tall vase -->
	<path d="M150 430 C 118 400, 116 330, 140 290 C 152 270, 150 245, 140 230 L200 230 C 190 245, 188 270, 200 290 C 224 330, 222 400, 190 430 Z" fill="url(#ps-art-vase)"/>
	<ellipse cx="170" cy="230" rx="30" ry="6" style="fill:color-mix(in srgb, var(--ps-accent) 70%, black)" opacity=".35"/>
	<!-- branch -->
	<path d="M170 232 C 175 170, 200 120, 250 80" fill="none" style="stroke:var(--ps-primary)" stroke-width="2.5" stroke-linecap="round"/>
	<path d="M190 160 C 215 150, 232 158, 246 150 C 228 136, 205 140, 190 160Z" style="fill:var(--ps-primary)" opacity=".85"/>
	<path d="M205 125 C 196 104, 204 88, 200 72 C 218 86, 220 108, 205 125Z" style="fill:var(--ps-primary)" opacity=".75"/>
	<path d="M178 196 C 152 190, 140 172, 124 168 C 140 192, 158 200, 178 196Z" style="fill:var(--ps-primary)" opacity=".65"/>
	<!-- sphere & bowl -->
	<circle cx="395" cy="430" r="50" fill="url(#ps-art-dark)"/>
	<ellipse cx="380" cy="412" rx="14" ry="9" fill="#fff" opacity=".18"/>
	<path d="M325 480 C 330 510, 360 520, 395 520 C 430 520, 460 510, 465 480 Z" style="fill:var(--ps-surface);stroke:var(--ps-border)"/>
	<!-- small box -->
	<rect x="440" y="440" width="70" height="40" rx="4" style="fill:color-mix(in srgb, var(--ps-accent) 55%, var(--ps-surface))"/>
	<rect x="470" y="440" width="10" height="40" style="fill:var(--ps-surface)" opacity=".7"/>
	<!-- shadows -->
	<ellipse cx="170" cy="432" rx="52" ry="7" style="fill:var(--ps-primary)" opacity=".12"/>
	<ellipse cx="395" cy="482" rx="60" ry="6" style="fill:var(--ps-primary)" opacity=".1"/>
</svg>
