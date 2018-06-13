<!DOCTYPE html>
<html lang="en" class="@$section">
<head>
	<title>@pageTitle()</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.3.0/css/swiper.min.css" type="text/css" />
	<link rel="stylesheet" href="/files/build-dev/main.css?@rand()" type="text/css" />
</head>
<body class="page-@$page">


@renderContent()


<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" ></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.3.0/js/swiper.min.js"></script>
<script src="/files/js/main.js{{ app('dev') ? '?' . rand() : '' }}"></script>

@chunk( 'post-scripts' )
</body>
</html>