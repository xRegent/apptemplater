<!DOCTYPE html>
<html lang="en" class="@generate('folder-slug')">
<head>
	<title>@generate('page-title')</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	@generate("styles")
</head>
<body class="page-@$this->levels->last">


@renderLevel()


@generate("scripts")
@chunk( 'post-scripts' )
</body>
</html>