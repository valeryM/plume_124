<?php
pxTemplateInit('remove_numbers');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php pxInfo('encoding'); ?>" />
<meta name="MSSmartTagsPreventParsing" content="TRUE" />
<title>Search - <?php pxInfo('name'); ?></title>
<?php include(dirname(__FILE__).'/inc/head-link.php'); ?>
<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />
<?php pxHeadLinks(); ?>
<meta name="DC.title" content="Search - <?php pxInfo('name'); ?>" />
<?php include(dirname(__FILE__).'/inc/head-meta.php'); ?>
</head>

<body class="category">

<div id="page">
	<div id="banner">
	<?php include(dirname(__FILE__).'/inc/easy-access.php'); ?>
		
		<h1 id="top"><a href="<?php pxInfo('url'); ?>"><?php pxInfo('name'); ?></a></h1>
		<p class="description"><?php pxInfo('description'); ?></p>
	</div><!-- end banner -->


<div id="main">

<div id="mainfloat">
	<div id="content">
		<h2 class="comment-preview"><?php echo __('Results'); ?></h2>
    <p><?php echo __('Results of the search on:'); ?> <?php pxSearchQuery('<strong>%s</strong>'); ?>.</p>

<?php while (!$res->EOF()): ?>
    <div class="resource">
	    <h2><a href="<?php pxResPath(); ?>"><?php pxResTitle('%s'); ?></a></h2>
	    <p class="modified"><?php echo __('On'); ?> <a href="<?php pxResPath(); ?>"><?php pxResDateModification(__('%Y-%m-%d</a> at %H:%M')); ?>
	    <?php echo __('by'); ?> <a href="<?php pxResAuthorEmail('mailto:%s'); ?>"><?php pxResAuthor(); ?></a>.<?php pxResCategories(__(' In %s'), ', ', __(' and ')); ?>.</p>

			<?php  pxResDescription('<p>%s...</p>', 200); ?>
			<p class="score"><?php echo __('Score:'); ?> <?php pxResSearchScore(); ?></p>
    </div><!-- end resource -->
<?php 
$res->moveNext(); 
endwhile; ?>

		<hr class="invisible" />
	</div><!-- end content -->



	<div id="menuleft">
		<div class="col-content">
			<h2><?php echo __('Categories'); ?></h2>
			<?php pxPrimaryCategories('<ul id="top-categories">%s</ul>'); ?>

			<h2><?php echo __('Links'); ?></h2>
			<?php pxLink::linkList(); ?>

		</div><!-- end col-content -->
	</div><!-- end menuleft -->

</div><!-- end mainfloat -->



	<div id="menuright">
		<div class="col-content">

		<h2><?php echo __('Main categories'); ?></h2>
		<?php pxPrimaryCategories('<ul id="top-categories">%s</ul>'); ?>
		
			<?php include(dirname(__FILE__).'/inc/recent-news.php'); ?>
			<?php include(dirname(__FILE__).'/inc/rss-sitemap.php'); ?>
		</div><!-- col-content -->
	</div><!-- end menuright -->


</div><!-- end main -->

<?php include(dirname(__FILE__).'/inc/footer.php'); ?>