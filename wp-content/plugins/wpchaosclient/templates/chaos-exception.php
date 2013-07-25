<h1 class="text-error">Woups ... Something unexpected happend in the CHAOS:\_</h1>
<h2 class="text-error">Don't you worrie - this is probably not your fault.</h2>
<p class="text-error"><?php echo htmlentities($exception->getMessage()) ?></p>
<pre class="text-error"><?php echo htmlentities($exception->getTraceAsString()) ?></pre>