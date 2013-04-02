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

        <table class="summary" width="100%">
            <tr>
                <td class="summaryhead">
					Summary
                </td>
                <td class="summaryhead" width="10%">
					Strength
                </td>
             </tr>
        <xsl:for-each select="rootnode/techprojects/requirements/requirement">
        <xsl:sort select="ix" data-type="number"/>
            <tr>
                <td>
					<xsl:element name="a">
					<xsl:attribute name="href">#R<xsl:value-of select="nodecode"/></xsl:attribute>
					<xsl:attribute name="class">nodelink<xsl:value-of select="deepness"/></xsl:attribute>
                    <span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
                    <xsl:value-of select="abstract"/><br/>
                    </xsl:element>
                </td>
                <td>
					<xsl:value-of select="strength"/><br/>
                </td>
            </tr>
        </xsl:for-each>        
        </table>

        <xsl:for-each select="rootnode/techprojects/requirements/requirement">
        <xsl:sort select="ix" data-type="number"/>
			<xsl:element name="a">
			<xsl:attribute name="name">R<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
            <xsl:value-of select="description" disable-output-escaping="yes"/></div>
        </xsl:for-each>

       <!-- printing specification section -->
        <h3>Specifications</h3>

        <table class="summary" width="100%">
            <tr>
                <td class="summaryhead">
					Summary
                </td>
                 <td class="summaryhead" width="10%">
					Priority
                </td>
                <td class="summaryhead" width="10%">
					Severity
                </td>
                <td class="summaryhead" width="10%">
					Complexity
                </td>
            </tr>
        <xsl:for-each select="rootnode/techprojects/specifications/specification">
        <xsl:sort select="ix" data-type="number"/>
            <tr>
                <td>
					<xsl:element name="a">
					<xsl:attribute name="href">#S<xsl:value-of select="nodecode"/></xsl:attribute>
					<xsl:attribute name="class">nodelink<xsl:value-of select="deepness"/></xsl:attribute>
                    <span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
                    <xsl:value-of select="abstract"/>
                    </xsl:element><br/>
                </td>
                <td>
					<xsl:value-of select="priority"/>
				</td>
				<td>
					<xsl:value-of select="severity"/>
				</td>
				<td>
					<xsl:value-of select="complexity"/>
                </td>
            </tr>
        </xsl:for-each>        
        </table>

        <xsl:for-each select="rootnode/techprojects/specifications/specification">
        <xsl:sort select="ix" data-type="number"/>
			<xsl:element name="a">
			<xsl:attribute name="name">S<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
			<xsl:choose>
				<xsl:when test="string-length(description)!=0"><xsl:value-of select="description" disable-output-escaping="yes" /></xsl:when>
				<xsl:otherwise>No description</xsl:otherwise>
			</xsl:choose></div>
            </xsl:for-each>        

       <!-- printing deliverable section -->
        <h3>Deliverables</h3>

        <table class="summary" width="100%">
            <tr>
                <td class="summaryhead">
					Summary
                </td>
                <td class="summaryhead" width="10%">
					Status
                </td>
             </tr>
        <xsl:for-each select="rootnode/techprojects/deliverables/deliverable">
        <xsl:sort select="ix" data-type="number"/>
            <tr>
                <td>
					<xsl:element name="a">
					<xsl:attribute name="href">#D<xsl:value-of select="nodecode"/></xsl:attribute>
					<xsl:attribute name="class">nodelink<xsl:value-of select="deepness"/></xsl:attribute>
                    <span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
                    <xsl:value-of select="abstract"/><br/>
                    </xsl:element>
                </td>
                <td>
					<xsl:value-of select="status"/>
                </td>
            </tr>
        </xsl:for-each>        
        </table>

        <xsl:for-each select="rootnode/techprojects/deliverables/deliverable">
        <xsl:sort select="ix" data-type="number"/>
			<xsl:element name="a">
			<xsl:attribute name="name">D<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
			<xsl:choose>
				<xsl:when test="string-length(description)!=0"><xsl:value-of select="description" disable-output-escaping="yes" /></xsl:when>
				<xsl:otherwise>No description</xsl:otherwise>
			</xsl:choose>
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

        <table class="summary" width="100%">
            <tr>
                <td class="summaryhead">
					Summary
                </td>
             </tr>
        <xsl:for-each select="rootnode/techprojects/milestones/milestone">
        <xsl:sort select="ix" data-type="number" />
            <tr>
                <td>
					<xsl:element name="a">
					<xsl:attribute name="href">#M<xsl:value-of select="nodecode"/></xsl:attribute>
					<xsl:attribute name="class">nodelink<xsl:value-of select="deepness"/></xsl:attribute>
                    <span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
                    <xsl:value-of select="abstract"/><br/>
                    </xsl:element>
                </td>
            </tr>
        </xsl:for-each>        
        </table>

        <xsl:for-each select="rootnode/techprojects/milestones/milestone">
        <xsl:sort select="ix" data-type="number"/>
			<xsl:element name="a">
			<xsl:attribute name="name">M<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
			<xsl:choose>
				<xsl:when test="string-length(description)!=0"><xsl:value-of select="description" disable-output-escaping="yes" /></xsl:when>
				<xsl:otherwise>No description</xsl:otherwise>
			</xsl:choose></div>
            </xsl:for-each>        

        <h3>Tasks</h3>

        <table class="summary" width="100%">
            <tr>
                <td class="summaryhead">
					Summary
                </td>
                <td class="summaryhead">
					WTYPE
                </td>
                <td class="summaryhead">
					STATUS
                </td>
                <td class="summaryhead">
					DONE
                </td>
                <td class="summaryhead">
					MILE
                </td>
             </tr>
        <xsl:for-each select="rootnode/techprojects/tasks/task">
        <xsl:sort select="milestoneid"/>
            <tr>
                <td>
					<xsl:element name="a">
					<xsl:attribute name="href">#T<xsl:value-of select="nodecode"/></xsl:attribute>
					<xsl:attribute name="class">nodelink<xsl:value-of select="deepness"/></xsl:attribute>
                    <span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
                    <xsl:value-of select="abstract"/><br/>
                    </xsl:element>
                </td>
                <td width="10%">
					<xsl:value-of select="worktype"/>
                </td>
                <td width="10%">
					<xsl:value-of select="status"/>
                </td>
                <td width="10%">
					<xsl:value-of select="done"/> %
                </td>
                <td width="10%">
					<xsl:value-of select="milestoneid"/>
                </td>
            </tr>
        </xsl:for-each>        
        </table>

        <xsl:for-each select="rootnode/techprojects/tasks/task">
        <xsl:sort select="ix" data-type="number"/>
			<xsl:element name="a">
			<xsl:attribute name="name">T<xsl:value-of select="nodecode"/></xsl:attribute>
			</xsl:element>
            <div class="node"><span class="numbering"><xsl:value-of select="nodecode"/>. </span> 
            <b><xsl:value-of select="abstract"/></b><br/>
            <table class="itemsummary">
				<tbody>
					<tr>
						<th width="20%">START</th>
						<th width="20%">END</th>
						<th width="10%">COSTRATE</th>
						<th width="10%">COST</th>
						<th width="10%">PLANNED</th>
						<th width="10%">DONE</th>
						<th width="10%">STATUS</th>
						<th width="10%">MILESTONE</th>
					</tr>
					<tr>
						<td align="center"><xsl:choose>
							<xsl:when test="taskstartenable != 0"><xsl:value-of select="taskstart"/></xsl:when>
							<xsl:otherwise>N.C.</xsl:otherwise>
						</xsl:choose>
						</td>
						<td align="center"><xsl:choose>
							<xsl:when test="taskendenable != 0"><xsl:value-of select="taskend"/></xsl:when>
							<xsl:otherwise>N.C.</xsl:otherwise>
						</xsl:choose>
						</td>
						<td>
							<xsl:value-of select="costrate"/>
						</td>
						<td>
							<xsl:value-of select="quoted"/>
						</td>
						<td>
							<xsl:value-of select="planned"/>
						</td>
						<td>
							<xsl:value-of select="done"/> %
						</td>
						<td>
							<xsl:value-of select="taskstatus"/>
						</td>
						<td>
							M<xsl:value-of select="milestoneid"/>
						</td>
					</tr>
				</tbody>
			</table>
            <xsl:choose>
				<xsl:when test="string-length(description)!=0"><xsl:value-of select="description" disable-output-escaping="yes" /></xsl:when>
				<xsl:otherwise>No description</xsl:otherwise>
			</xsl:choose>
            </div>
        </xsl:for-each>        

        </blockquote>
    </body>
    </html>
	</xsl:template>
	
	
</xsl:stylesheet>