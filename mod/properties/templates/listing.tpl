<div id="roommate-link">{ROOMMATE}</div>
<div style="margin : 1em"><ul class="nav nav-tabs">{ALL}{NEW}{SUB}</ul></div>
<div id="properties">
    <a id="top"></a>
    {EMPTY_MESSAGE}
    <!-- BEGIN listrows -->
    <div class="property-listing">
        {THUMBNAIL}
        <div class="property-info">
            <h2 class="property-name">{NAME}</h2>
            <div class="address">{ADDRESS}<br />
                <i>{CAMPUS_DISTANCE} from campus</i></div>
            <div class="details">Price: ${MONTHLY_RENT} per month<br />
                Bedrooms: {BEDROOM_NO}<br />
                Bathrooms: {BATHROOM_NO}<br />
                Availablity: {MOVE_IN_DATE}
            </div>
        </div>
    </div>
    <!-- END listrows -->
    <hr />
    <div class="text-center">
        <div>{PAGE_LABEL} {PAGES}</div>
        <div>Properties shown: {TOTAL_ROWS}</div>
        <div>{LIMIT_LABEL} {LIMITS}</div>
    </div>
    <div class="align-right">{SEARCH}</div>
</div>
<a href="#top">Back to top</a>