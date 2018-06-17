@renderLevel()


<style>
	.-level-error {
		font-size: 20px;
		line-height: 38px;
		padding: 30px 5px 30px 50px;
		background: #ffdada;
		border-bottom: 1px solid #f19898;
		box-shadow: 0 5px 40px #652424;
		text-align: center;
		margin: 0;
		margin-bottom: 60px;
	}
	.-level-block {
		padding: 15px;
		margin: 10px 0;
		border-left: 2px solid green;
		text-align: left;
	}
		.-level-link {
			display: inline-block;
			padding: 15px;
			margin: -15px 0px 10px -15px;
			border: 2px solid green;
			background: rgba(0, 128, 0, 0.08);
			border-left: none;
			font-size: 20px;
			text-decoration: none;
			color: #000;
			font-weight: bold;
		}
			.-level-link:hover {
				text-decoration: none;
				background: rgba(0, 0, 128, 0.2);
			}
		.-level-separator {
			display: block;
			margin: 30px 0;
		}

	.-text-red {
		color: #b30202;
	}

	.-btn-main {
		font-size:26px;
		padding: 20px 40px;
	}

	.-btn-main-mini {
		font-size: 18px;
		padding: 10px 40px;
		width: 200px;
	}
	.-btn-page {
		font-size: 20px;
		margin: 8px;
		border-width: 0;
	}
		.-btn-page:hover {
			background-color: black !important;
		}

	.-component-not-found {
		font-size: 22px;
		text-align: center;
	}
		.-component-not-found .btn {
			margin: 5px;
		}

	.component-name {
		display: block;
		background: #231803;
		padding: 10px 20px;
		font-size: 28px;
		box-shadow: -10px 0 10px #2d220d;
		text-align: center;
		position: -webkit-sticky;
		position: sticky;
		top: -1px;
		margin-top: 100px;
		color: #f9cb9a;
	}
		.component-name:hover {
			text-decoration: none;
			opacity: 0.9;
			color: orange;
		}

	.component-code {
		position: relative;
	}
		.component-code:after {
			content: 'CODE';
			position: absolute;
			top: 0;
			right: 0;
			bottom: 0;
			background: rgba( 255, 165, 0, 0.6 );
			padding: 42px 8px 5px 8px;
			font-weight: bold;
			pointer-events: none;
		}
		.component-code:hover:after {
			display: none;
		}
		.component-code textarea {
			color: white;
			display: block;
			width: 100%;
			padding: 20px 5px 20px 30px;
			padding-bottom: 3px;
			font-size: 20px;
			border: 0;
			height: auto;
			background: rgba(35, 24, 3, 0.9);
			box-shadow: -10px 0 10px #2d220d;
		}
</style>