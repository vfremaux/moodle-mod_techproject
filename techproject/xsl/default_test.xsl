<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/">
	<html>
	<head>
	<title><xsl:value-of select="rootnode/techprojects/techproject/name" /></title>
	<xsl:element name="link">
	    <xsl:attribute name="href"><xsl:value-of select="rootnode/techprojects/techproject/wwwroot"/>/mod/techproject/xsl/default.css</xsl:attribute>
	    <xsl:attribute name="type">text/css</xsl:attribute>
	    <xsl:attribute name="rel">stylesheet</xsl:attribute>
	</xsl:element>
	</head>
	<body>
        <h1><xsl:value-of select="rootnode/techprojects/projects/project/title" /></h1>
        <h3><xsl:value-of select="rootnode/techprojects/description"/></h3>
        <h2>Project overview</h2>
        <blockquote>
        <h3>Abstract</h3>
        <p><xsl:value-of select="rootnode/techprojects/projects/project/abstract" disable-output-escaping="yes"/></p>
        <h3>Rationale</h3>
        <p><xsl:value-of select="rootnode/techprojects/projects/project/rationale" disable-output-escaping="yes"/></p>
        <h3>Environment</h3>
        <p><xsl:value-of select="rootnode/techprojects/projects/project/environment" disable-output-escaping="yes"/></p>
        <h3>Organisation</h3>
        <p><xsl:value-of select="rootnode/techprojects/projects/project/organisation" disable-output-escaping="yes"/></p>
        </blockquote>
        <h2>Project map</h2>
        <blockquote>
       
       <!-- printing requirement section -->
        <h3>Requirements</h3>

        <xsl:for-each select="rootnode/techprojects/requirements/requirement">
        <xsl:sort select="ordering"/>
			<xsl:element name="a">
			<xsl:attribute name="name">R<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
            <xsl:value-of select="description"/></div>
        </xsl:for-each>

       <!-- printing specification section -->
        <h3>Specifications</h3>

        <xsl:for-each select="rootnode/techprojects/specifications/specification">
        <xsl:sort select="ordering"/>
			<xsl:element name="a">
			<xsl:attribute name="name">S<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
            <p><xsl:value-of select="description"/></p></div>
        </xsl:for-each>        

       <!-- printing deliverable section -->
        <h3>Deliverables</h3>

        <xsl:for-each select="rootnode/techprojects/deliverables/deliverable">
        <xsl:sort select="ordering"/>
			<xsl:element name="a">
			<xsl:attribute name="name">D<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
            <xsl:value-of select="description"/>
            <p align="right">
            <xsl:element name="a">
            <xsl:attribute name="href"><xsl:value-of select="localfile"/></xsl:attribute>
            <xsl:attribute name="target">_blank</xsl:attribute>
            </xsl:element>
            </p>
            </div>
        </xsl:for-each>        
        </blockquote>
        <h2>Project work</h2>
        <blockquote>

        <h3>Milestones</h3>

        <xsl:for-each select="rootnode/techprojects/milestones/milestone">
        <xsl:sort select="ordering"/>
			<xsl:element name="a">
			<xsl:attribute name="name">M<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
            <xsl:value-of select="description"/></div>
        </xsl:for-each>        

        <h3>Tasks</h3>

        <xsl:for-each select="rootnode/techprojects/tasks/task">
        <xsl:sort select="ordering"/>
			<xsl:element name="a">
			<xsl:attribute name="name">T<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/><br/>
			<i>Start:</i> <xsl:choose>
				<xsl:when test="taskstartenable != 0"><xsl:value-of select="taskstart"/></xsl:when>
				<xsl:otherwise>N.C.</xsl:otherwise>
			</xsl:choose><br/>
			<i>End:</i> <xsl:choose>
				<xsl:when test="taskendenable != 0"><xsl:value-of select="taskend"/></xsl:when>
				<xsl:otherwise>N.C.</xsl:otherwise>
			</xsl:choose><br/>
			<i>Costrate:</i> <xsl:value-of select="costrate"/><br/>
			<i>Cost:</i> <xsl:value-of select="cost"/><br/>
			<i>Planned:</i> <xsl:value-of select="planned"/><br/>
			<i>Done:</i> <xsl:value-of select="done"/> %<br/>
			<i>Status:</i> <xsl:value-of select="taskstatus"/><br/>
			<i>Milestone:</i> M<xsl:value-of select="milestoneid"/><br/>
            <p><xsl:choose>
				<xsl:when test="string-length(description)!=0"><xsl:value-of select="description"/></xsl:when>
				<xsl:otherwise>No description</xsl:otherwise>
			</xsl:choose></p>
            </div>
        </xsl:for-each>        

        </blockquote>
    </body>
    </html>
	</xsl:template>
	
	
</xsl:stylesheet>