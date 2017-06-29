<?php
define( 'WC_DECISION_MATRIC_PATH', getcwd() );

include( 'includes/class-issues.php' );

$issuesAPI = new Issues();
$issues    = $issuesAPI->get_data( isset( $_POST['refresh'] ) );
$cells     = array_fill_keys( range( 0, 40 ), array_fill_keys( range( 0, 40 ), array() ) );

foreach ( $issues as $issue ) {
	// Calc effort.
	$estimate = $issue->estimate;

	// Calc impact.
	$impact   = isset( $issue->impact ) ? $issue->impact : 'medium';

	switch ( $impact ) {
		case 'very-low' :
			$impact = 40;
		break;
		case 'low' :
			$impact = 30;
		break;
		case 'medium' :
			$impact = 20;
		break;
		case 'high' :
			$impact = 10;
		break;
		case 'mind-blown' :
			$impact = 0;
		break;
	}

	// Adjust for age and other metrics.
	$datediff   = time() - strtotime( $issue->created );
	$days       = floor( $datediff / ( 60 * 60 * 24 ) );
	$day_weight = $days / 90; // Older issues need less importance/more effort or they'd be done already :)
	$impact    -= $day_weight;
	$estimate  += $day_weight;

	// More comments = more importance.
	$impact  -= ceil( $issue->comments / 10 );

	$estimate = max( 0, min( 40, ceil( $estimate ) ) );
	$impact   = max( 0, min( 40, ceil( $impact ) ) );

	$cells[ $estimate ][ $impact ][] = $issue;
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>WooCommerce Issue Decision Matrix</title>
		<meta charset="utf-8" />
		<link rel="stylesheet" href="http://local.wordpress.dev/wp-content/plugins/WC-Issue-Decision-Matrix/assets/css/font-awesome.css" />

		<style type="text/css">
			body {
				padding: 20px;
				font-family: helvetica;
				text-align: center;
			}
			h1 {
				text-align: center;
			}
			form {
				float: right;
			}
			td {
				vertical-align: middle;
				text-align: center;
			}
			.matrix {
				border-left: 5px solid #eee;
				border-bottom: 5px solid #eee;
			}
			.matrix-wrapper td {
				padding: 1em;
			}
			.row-20 {
				background: #eee;
			}
			.col-20 {
				background: #eee;
			}
			.matrix td {
				padding: 0;
				margin: 0;
			}
			.matrix td .inner {
				width: 25px;
				height: 25px;
				position: relative;
			}
			div.issue {
				border: 1px solid rgba(0,0,0,0.1);
				background-color: #fcffa3;
				color:black;
				display: block;
				padding: .5em;
				text-decoration: none;
				box-shadow: 2px 2px 2px rgba(0,0,0,0.2);
				width: 75px;
				min-height: 75px;
				position: absolute;
				margin-left: -32px;
				margin-top: -32px;
				z-index: 1;
				transition: transform 0.25s linear;
				font-size: 12px;
			}

			div.issue.issue-1 {
				transform: rotate(-10deg);
			}
			div.issue.issue-2 {
				transform: rotate(10deg);
			}
			div.issue.issue-3 {
				transform: rotate(-20deg);
			}
			div.issue.issue-4 {
				transform: rotate(20deg);
			}

			div.issue:hover {
				z-index: 10;
			}
			div.issue a {
				display: block;
				color: #000;
				margin-bottom: .5em;
			}

			.segment-overlay {
				font-size: 2em;
				position: relative;
				text-align: left;
			}
			.segment-overlay i {
				width: 512px;
				height: 512px;
				position: absolute;
				line-height: 512px;
				text-align: center;
				color: #eee;
				font-size: 8em;
			}
			.quick-wins {

			}
			.thankless-tasks {
				margin-top: 512px;
				margin-left: 512px;
			}
			.major-projects {
				margin-left: 512px;
			}
			.fill-ins {
				margin-top: 512px;
			}
		</style>
	</head>
	<body>
		<form method="post">
			<input type="hidden" name="refresh" value="1" />
			<input type="submit" value="Refresh" />
		</form>
		<h1>WooCommerce Issue Decision Matrix</h1>
		<table class="matrix-wrapper" cellspacing="0">
			<tr>
				<td>High</td>
				<td colspan="3" rowspan="3">
					<div class="segment-overlay">
						<i class="fa fa-smile-o quick-wins" aria-hidden="true"></i>
						<i class="fa fa-frown-o thankless-tasks" aria-hidden="true"></i>
						<i class="fa fa-meh-o major-projects" aria-hidden="true"></i>
						<i class="fa fa-hourglass fill-ins" aria-hidden="true"></i>
					</div>
					<table class="matrix" cellspacing="0">
						<?php
							$cols = 40;
							$rows = 40;

							for ( $row = 0; $row <= $rows; $row ++ ) {
								echo '<tr>';
								for ( $col = 0; $col <= $cols; $col ++ ) {
									echo '<td class="row-' . $row . ' col-' . $col . '"><div class="inner">';
									if ( ! empty( $cells[ $col ][ $row ] ) ) {
										$render_issues = $cells[ $col ][ $row ];
										foreach ( $render_issues as $index => $render_issue ) {
											echo '<div class="issue issue-' . $index . '"><a href="' . $render_issue->url . '">#' . $render_issue->number . '</a> <small>' . $render_issue->title . '</small></div>';
										}
									}
									echo '</div></td>';
								}
								echo '</tr>';
							}
						?>
					</table>
				</td>
			</tr>
			<tr>
				<td>Impact</td>
				<td></td>
			</tr>
			<tr>
				<td>Low</td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td>Low</td>
				<td>Effort</td>
				<td>High</td>
			</tr>
		</table>
	</body>
</html>
