<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Product detail</title>
    <link href="../css/bootstrap.css" rel="stylesheet" >
    <link href="../css/font-awesome.min.css" rel="stylesheet" >
    <link href="../css/jquery.bxslider.css" rel="stylesheet" >
    <link href="../css/style.css" rel="stylesheet" >
 </head>
<body>
<div class="col-lg-4 col-sm-4 hero-feature text-center">
	                <div class="thumbnail">
	                	<a href="#" class="link-p" style="overflow: hidden; position: relative;">
	                    	<img src="{{ $image }}" alt="" style="position: absolute; width: 340px; height: 340px; max-width: none; max-height: none; left: 0px; top: 0px;">
	                	</a>
	                    <div class="caption prod-caption">
	                        <h4><a href="#">{{ $name }}</a></h4>
	                        <p>{{ $description }}</p>
	                        <p>
	                        	</p><div class="btn-group">
	                        				                        	<a href="#" class="btn btn-default">Rp 	{{ number_format($price, 0, '', '.') }}</a>
		                        		                        	</div>
	                        <p></p>
	                    </div>
	                </div>
	            </div>
   
   </body>
</html>
