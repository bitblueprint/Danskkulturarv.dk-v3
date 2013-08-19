<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="text"/>
	<xsl:template match="Result[@FullName='CHAOS.Portal.Indexing.Extension.DTO.FacetsResult']/Facets">
		<xsl:for-each select="Result">
			<xsl:sort select="@Count" data-type="number" order="descending" />
			<xsl:value-of select="Value" /><xsl:text> har leveret </xsl:text><xsl:value-of select="@Count" /> objekter.
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>