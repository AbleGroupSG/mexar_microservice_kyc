<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>MEXAR KYC MSA API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "http://localhost:8888";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.5.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.5.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-GETapi-user">
                                <a href="#endpoints-GETapi-user">GET api/user</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-entity-onboarding" class="tocify-header">
                <li class="tocify-item level-1" data-unique="entity-onboarding">
                    <a href="#entity-onboarding">Entity Onboarding</a>
                </li>
                                    <ul id="tocify-subheader-entity-onboarding" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="entity-onboarding-POSTapi-v1-e-form-onboarding">
                                <a href="#entity-onboarding-POSTapi-v1-e-form-onboarding">Submit individual onboarding check</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-kyb-screening" class="tocify-header">
                <li class="tocify-item level-1" data-unique="kyb-screening">
                    <a href="#kyb-screening">KYB Screening</a>
                </li>
                                    <ul id="tocify-subheader-kyb-screening" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="kyb-screening-POSTapi-v1-e-form-kyb">
                                <a href="#kyb-screening-POSTapi-v1-e-form-kyb">Submit KYB screening request</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-kyc-screening" class="tocify-header">
                <li class="tocify-item level-1" data-unique="kyc-screening">
                    <a href="#kyc-screening">KYC Screening</a>
                </li>
                                    <ul id="tocify-subheader-kyc-screening" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="kyc-screening-POSTapi-v1-screen">
                                <a href="#kyc-screening-POSTapi-v1-screen">Submit KYC screening request</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="kyc-screening-GETapi-v1-status--uuid-">
                                <a href="#kyc-screening-GETapi-v1-status--uuid-">Get KYC screening status</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-ocr-services" class="tocify-header">
                <li class="tocify-item level-1" data-unique="ocr-services">
                    <a href="#ocr-services">OCR Services</a>
                </li>
                                    <ul id="tocify-subheader-ocr-services" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="ocr-services-POSTapi-v1-ocr">
                                <a href="#ocr-services-POSTapi-v1-ocr">Process OCR for identity documents</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-system-information" class="tocify-header">
                <li class="tocify-item level-1" data-unique="system-information">
                    <a href="#system-information">System Information</a>
                </li>
                                    <ul id="tocify-subheader-system-information" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="system-information-GETapi-v1-providers">
                                <a href="#system-information-GETapi-v1-providers">List KYC Providers</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: January 6, 2026</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8888</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-GETapi-user">GET api/user</h2>

<p>
</p>



<span id="example-requests-GETapi-user">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8888/api/user" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/user"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-user">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
access-control-allow-origin: *
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-user" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-user"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-user"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-user" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-user">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-user" data-method="GET"
      data-path="api/user"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-user', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-user"
                    onclick="tryItOut('GETapi-user');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-user"
                    onclick="cancelTryOut('GETapi-user');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-user"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/user</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-user"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-user"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="entity-onboarding">Entity Onboarding</h1>

    

                                <h2 id="entity-onboarding-POSTapi-v1-e-form-onboarding">Submit individual onboarding check</h2>

<p>
</p>

<p>Performs compliance screening for individual entity onboarding using RegTank. This endpoint checks individuals against sanctions lists, PEP databases, and adverse media for customer due diligence and compliance purposes.</p>

<span id="example-requests-POSTapi-v1-e-form-onboarding">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8888/api/v1/e-form-onboarding" \
    --header "X-API-KEY: Your API key for authentication" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"john.doe@example.com\",
    \"surname\": \"Doe\",
    \"forename\": \"John\",
    \"countryOfResidence\": \"SG\",
    \"placeOfBirth\": \"Singapore\",
    \"nationality\": \"SG\",
    \"idIssuingCountry\": \"SG\",
    \"dateOfBirth\": \"1985-03-15\",
    \"gender\": \"Male\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/v1/e-form-onboarding"
);

const headers = {
    "X-API-KEY": "Your API key for authentication",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "john.doe@example.com",
    "surname": "Doe",
    "forename": "John",
    "countryOfResidence": "SG",
    "placeOfBirth": "Singapore",
    "nationality": "SG",
    "idIssuingCountry": "SG",
    "dateOfBirth": "1985-03-15",
    "gender": "Male"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-e-form-onboarding">
            <blockquote>
            <p>Example response (200, Onboarding screening request successfully submitted to RegTank):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Screening successful&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440020&quot;
    },
    &quot;data&quot;: {
        &quot;requestId&quot;: &quot;REG-789012&quot;,
        &quot;status&quot;: &quot;pending&quot;,
        &quot;email&quot;: &quot;john.doe@example.com&quot;,
        &quot;name&quot;: &quot;John Doe&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation error - email is required and must be valid):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 422,
        &quot;message&quot;: &quot;Validation failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440021&quot;
    },
    &quot;errors&quot;: {
        &quot;email&quot;: [
            &quot;The email field is required.&quot;,
            &quot;The email must be a valid email address.&quot;
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (500, Provider error or internal server error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 500,
        &quot;message&quot;: &quot;Screening failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440022&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;RegTank API connection failed&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-e-form-onboarding" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-e-form-onboarding"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-e-form-onboarding"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-e-form-onboarding" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-e-form-onboarding">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-e-form-onboarding" data-method="POST"
      data-path="api/v1/e-form-onboarding"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-e-form-onboarding', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-e-form-onboarding"
                    onclick="tryItOut('POSTapi-v1-e-form-onboarding');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-e-form-onboarding"
                    onclick="cancelTryOut('POSTapi-v1-e-form-onboarding');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-e-form-onboarding"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/e-form-onboarding</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-KEY</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-KEY"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="Your API key for authentication"
               data-component="header">
    <br>
<p>Example: <code>Your API key for authentication</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="john.doe@example.com"
               data-component="body">
    <br>
<p>Email address of the individual (required) Example: <code>john.doe@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>surname</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="surname"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="Doe"
               data-component="body">
    <br>
<p>Surname/Last name of the individual Example: <code>Doe</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>forename</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="forename"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="John"
               data-component="body">
    <br>
<p>Forename/First name of the individual Example: <code>John</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>countryOfResidence</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="countryOfResidence"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="SG"
               data-component="body">
    <br>
<p>Country of residence (ISO 3166-1 alpha-2 code) Example: <code>SG</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>placeOfBirth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="placeOfBirth"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="Singapore"
               data-component="body">
    <br>
<p>Place of birth (city or country) Example: <code>Singapore</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>nationality</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="nationality"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="SG"
               data-component="body">
    <br>
<p>Nationality (ISO 3166-1 alpha-2 code) Example: <code>SG</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>idIssuingCountry</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="idIssuingCountry"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="SG"
               data-component="body">
    <br>
<p>Country that issued the identification document (ISO 3166-1 alpha-2 code) Example: <code>SG</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dateOfBirth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dateOfBirth"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="1985-03-15"
               data-component="body">
    <br>
<p>Date of birth in YYYY-MM-DD format Example: <code>1985-03-15</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>gender</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="gender"                data-endpoint="POSTapi-v1-e-form-onboarding"
               value="Male"
               data-component="body">
    <br>
<p>Gender (Male, Female, or Other) Example: <code>Male</code></p>
        </div>
        </form>

                <h1 id="kyb-screening">KYB Screening</h1>

    

                                <h2 id="kyb-screening-POSTapi-v1-e-form-kyb">Submit KYB screening request</h2>

<p>
</p>

<p>Initiates a KYB (Know Your Business) screening process for company verification using RegTank's Dow Jones database. This endpoint screens businesses against sanctions lists, adverse media, and politically exposed persons databases.</p>

<span id="example-requests-POSTapi-v1-e-form-kyb">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8888/api/v1/e-form-kyb" \
    --header "X-API-KEY: Your API key for authentication" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"referenceId\": \"COMPANY-REF-123\",
    \"businessName\": \"Acme Corporation Ltd\",
    \"businessIdNumber\": \"123456789\",
    \"address1\": \"123 Business Street, Suite 100\",
    \"email\": \"contact@acme.com\",
    \"phone\": \"+1-555-0123\",
    \"website\": \"https:\\/\\/www.acme.com\",
    \"dateOfIncorporation\": \"2010-05-15\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/v1/e-form-kyb"
);

const headers = {
    "X-API-KEY": "Your API key for authentication",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "referenceId": "COMPANY-REF-123",
    "businessName": "Acme Corporation Ltd",
    "businessIdNumber": "123456789",
    "address1": "123 Business Street, Suite 100",
    "email": "contact@acme.com",
    "phone": "+1-555-0123",
    "website": "https:\/\/www.acme.com",
    "dateOfIncorporation": "2010-05-15"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-e-form-kyb">
            <blockquote>
            <p>Example response (200, KYB screening request successfully submitted to RegTank):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Screening successful&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440010&quot;
    },
    &quot;data&quot;: {
        &quot;requestId&quot;: &quot;REG-123456&quot;,
        &quot;status&quot;: &quot;pending&quot;,
        &quot;businessName&quot;: &quot;Acme Corporation Ltd&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400, RegTank provider error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 400,
        &quot;message&quot;: &quot;Screening failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440012&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;Provider connection failed&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation error - required fields missing):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 422,
        &quot;message&quot;: &quot;Validation failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440011&quot;
    },
    &quot;errors&quot;: {
        &quot;referenceId&quot;: [
            &quot;The reference id field is required.&quot;
        ],
        &quot;businessName&quot;: [
            &quot;The business name field is required.&quot;
        ],
        &quot;businessIdNumber&quot;: [
            &quot;The business id number field is required.&quot;
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (500, Internal server error):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 500,
        &quot;message&quot;: &quot;Screening failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440013&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;An internal error happened&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-e-form-kyb" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-e-form-kyb"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-e-form-kyb"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-e-form-kyb" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-e-form-kyb">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-e-form-kyb" data-method="POST"
      data-path="api/v1/e-form-kyb"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-e-form-kyb', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-e-form-kyb"
                    onclick="tryItOut('POSTapi-v1-e-form-kyb');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-e-form-kyb"
                    onclick="cancelTryOut('POSTapi-v1-e-form-kyb');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-e-form-kyb"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/e-form-kyb</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-KEY</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-KEY"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="Your API key for authentication"
               data-component="header">
    <br>
<p>Example: <code>Your API key for authentication</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>referenceId</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="referenceId"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="COMPANY-REF-123"
               data-component="body">
    <br>
<p>Your internal reference ID for tracking this business screening request Example: <code>COMPANY-REF-123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>businessName</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="businessName"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="Acme Corporation Ltd"
               data-component="body">
    <br>
<p>Legal name of the business entity Example: <code>Acme Corporation Ltd</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>businessIdNumber</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="businessIdNumber"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="123456789"
               data-component="body">
    <br>
<p>Business registration or tax identification number Example: <code>123456789</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>address1</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address1"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="123 Business Street, Suite 100"
               data-component="body">
    <br>
<p>Primary business address Example: <code>123 Business Street, Suite 100</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="contact@acme.com"
               data-component="body">
    <br>
<p>Business contact email address Example: <code>contact@acme.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="phone"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="+1-555-0123"
               data-component="body">
    <br>
<p>Business contact phone number Example: <code>+1-555-0123</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>website</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="website"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="https://www.acme.com"
               data-component="body">
    <br>
<p>Business website URL Example: <code>https://www.acme.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>dateOfIncorporation</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="dateOfIncorporation"                data-endpoint="POSTapi-v1-e-form-kyb"
               value="2010-05-15"
               data-component="body">
    <br>
<p>Date of business incorporation in YYYY-MM-DD format Example: <code>2010-05-15</code></p>
        </div>
        </form>

                <h1 id="kyc-screening">KYC Screening</h1>

    

                                <h2 id="kyc-screening-POSTapi-v1-screen">Submit KYC screening request</h2>

<p>
</p>

<p>Initiates an asynchronous KYC (Know Your Customer) screening process. This endpoint creates a KYC profile with PENDING status and returns a reference ID immediately. The actual verification is processed asynchronously by the selected provider (RegTank or GlairAI). Once processing completes, a webhook notification will be sent to your configured webhook URL, and you can also poll the status endpoint.</p>

<span id="example-requests-POSTapi-v1-screen">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8888/api/v1/screen" \
    --header "X-API-KEY: Your API key for authentication" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"personal_info\": {
        \"first_name\": \"John\",
        \"last_name\": \"Doe\",
        \"gender\": \"Male\",
        \"date_of_birth\": \"1990-01-15\",
        \"nationality\": \"ID\"
    },
    \"identification\": {
        \"id_type\": \"national_id\",
        \"id_number\": \"1234567890123456\",
        \"issuing_country\": \"ID\",
        \"issue_date\": \"2020-01-01\",
        \"expiry_date\": \"2030-01-01\"
    },
    \"address\": {
        \"street\": \"Jl. Sudirman\",
        \"city\": \"Jakarta\",
        \"state\": \"DKI Jakarta\",
        \"postal_code\": \"12190\",
        \"country\": \"ID\",
        \"address_line\": \"Jl. Sudirman No. 123\"
    },
    \"contact\": {
        \"email\": \"john.doe@example.com\",
        \"phone\": \"+62812345678\"
    },
    \"meta\": {
        \"service_provider\": \"regtank\",
        \"reference_id\": \"YOUR-REF-123\",
        \"status\": \"architecto\",
        \"test\": false
    }
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/v1/screen"
);

const headers = {
    "X-API-KEY": "Your API key for authentication",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "personal_info": {
        "first_name": "John",
        "last_name": "Doe",
        "gender": "Male",
        "date_of_birth": "1990-01-15",
        "nationality": "ID"
    },
    "identification": {
        "id_type": "national_id",
        "id_number": "1234567890123456",
        "issuing_country": "ID",
        "issue_date": "2020-01-01",
        "expiry_date": "2030-01-01"
    },
    "address": {
        "street": "Jl. Sudirman",
        "city": "Jakarta",
        "state": "DKI Jakarta",
        "postal_code": "12190",
        "country": "ID",
        "address_line": "Jl. Sudirman No. 123"
    },
    "contact": {
        "email": "john.doe@example.com",
        "phone": "+62812345678"
    },
    "meta": {
        "service_provider": "regtank",
        "reference_id": "YOUR-REF-123",
        "status": "architecto",
        "test": false
    }
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-screen">
            <blockquote>
            <p>Example response (200, KYC screening request accepted. The identity field contains the UUID for status polling. Use GET /api/status/{uuid} to check the screening status, or wait for webhook notification to your configured webhook URL.):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Screening successful&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440000&quot;
    },
    &quot;data&quot;: {
        &quot;identity&quot;: &quot;550e8400-e29b-41d4-a716-446655440000&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400, Provider error - the KYC provider returned an error or is unavailable):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 400,
        &quot;message&quot;: &quot;Screening failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440003&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;Provider connection failed&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Authentication failed - invalid or missing X-API-KEY header):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 401,
        &quot;message&quot;: &quot;Unauthorized&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440002&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;Unauthorized&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation error - required fields are missing or invalid):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 422,
        &quot;message&quot;: &quot;Validation failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440001&quot;
    },
    &quot;errors&quot;: {
        &quot;personal_info.nationality&quot;: [
            &quot;The personal info.nationality field is required.&quot;
        ],
        &quot;identification.id_type&quot;: [
            &quot;The identification.id type field is required.&quot;
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (500, Internal server error - an unexpected error occurred during processing):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 500,
        &quot;message&quot;: &quot;Screening failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440004&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;An internal error happened&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-screen" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-screen"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-screen"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-screen" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-screen">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-screen" data-method="POST"
      data-path="api/v1/screen"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-screen', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-screen"
                    onclick="tryItOut('POSTapi-v1-screen');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-screen"
                    onclick="cancelTryOut('POSTapi-v1-screen');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-screen"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/screen</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-KEY</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-KEY"                data-endpoint="POSTapi-v1-screen"
               value="Your API key for authentication"
               data-component="header">
    <br>
<p>Example: <code>Your API key for authentication</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-screen"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-screen"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>personal_info</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Personal information of the individual being screened</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>first_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal_info.first_name"                data-endpoint="POSTapi-v1-screen"
               value="John"
               data-component="body">
    <br>
<p>First name Example: <code>John</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>last_name</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal_info.last_name"                data-endpoint="POSTapi-v1-screen"
               value="Doe"
               data-component="body">
    <br>
<p>Last name Example: <code>Doe</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>gender</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal_info.gender"                data-endpoint="POSTapi-v1-screen"
               value="Male"
               data-component="body">
    <br>
<p>Gender (Male, Female, or Unspecified) Example: <code>Male</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>date_of_birth</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal_info.date_of_birth"                data-endpoint="POSTapi-v1-screen"
               value="1990-01-15"
               data-component="body">
    <br>
<p>Date of birth in YYYY-MM-DD format Example: <code>1990-01-15</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>nationality</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="personal_info.nationality"                data-endpoint="POSTapi-v1-screen"
               value="ID"
               data-component="body">
    <br>
<p>ISO 3166-1 alpha-2 country code (2 letters) Example: <code>ID</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>identification</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Identification document information</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>id_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="identification.id_type"                data-endpoint="POSTapi-v1-screen"
               value="national_id"
               data-component="body">
    <br>
<p>Type of identification document (e.g., national_id, passport) Example: <code>national_id</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>id_number</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="identification.id_number"                data-endpoint="POSTapi-v1-screen"
               value="1234567890123456"
               data-component="body">
    <br>
<p>Identification document number Example: <code>1234567890123456</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>issuing_country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="identification.issuing_country"                data-endpoint="POSTapi-v1-screen"
               value="ID"
               data-component="body">
    <br>
<p>ISO 3166-1 alpha-2 country code of issuing country Example: <code>ID</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>issue_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="identification.issue_date"                data-endpoint="POSTapi-v1-screen"
               value="2020-01-01"
               data-component="body">
    <br>
<p>Issue date in YYYY-MM-DD format Example: <code>2020-01-01</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>expiry_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="identification.expiry_date"                data-endpoint="POSTapi-v1-screen"
               value="2030-01-01"
               data-component="body">
    <br>
<p>Expiry date in YYYY-MM-DD format (must be after issue_date) Example: <code>2030-01-01</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>address</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Address information</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>street</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address.street"                data-endpoint="POSTapi-v1-screen"
               value="Jl. Sudirman"
               data-component="body">
    <br>
<p>Street name Example: <code>Jl. Sudirman</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>city</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address.city"                data-endpoint="POSTapi-v1-screen"
               value="Jakarta"
               data-component="body">
    <br>
<p>City name Example: <code>Jakarta</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>state</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address.state"                data-endpoint="POSTapi-v1-screen"
               value="DKI Jakarta"
               data-component="body">
    <br>
<p>State or province Example: <code>DKI Jakarta</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>postal_code</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address.postal_code"                data-endpoint="POSTapi-v1-screen"
               value="12190"
               data-component="body">
    <br>
<p>Postal or ZIP code Example: <code>12190</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>country</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address.country"                data-endpoint="POSTapi-v1-screen"
               value="ID"
               data-component="body">
    <br>
<p>ISO 3166-1 alpha-2 country code Example: <code>ID</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>address_line</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="address.address_line"                data-endpoint="POSTapi-v1-screen"
               value="Jl. Sudirman No. 123"
               data-component="body">
    <br>
<p>Full address line Example: <code>Jl. Sudirman No. 123</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>contact</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Contact information</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.email"                data-endpoint="POSTapi-v1-screen"
               value="john.doe@example.com"
               data-component="body">
    <br>
<p>Email address Example: <code>john.doe@example.com</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>phone</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="contact.phone"                data-endpoint="POSTapi-v1-screen"
               value="+62812345678"
               data-component="body">
    <br>
<p>Phone number Example: <code>+62812345678</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>meta</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
 &nbsp;
 &nbsp;
<br>
<p>Metadata for the screening request</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>service_provider</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="meta.service_provider"                data-endpoint="POSTapi-v1-screen"
               value="regtank"
               data-component="body">
    <br>
<p>KYC service provider to use (regtank, glair, or test) Example: <code>regtank</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>reference_id</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="meta.reference_id"                data-endpoint="POSTapi-v1-screen"
               value="YOUR-REF-123"
               data-component="body">
    <br>
<p>Your internal reference ID for tracking this request Example: <code>YOUR-REF-123</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>status</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="meta.status"                data-endpoint="POSTapi-v1-screen"
               value="architecto"
               data-component="body">
    <br>
<p>Optional status override (for internal use) Example: <code>architecto</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>test</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-screen" style="display: none">
            <input type="radio" name="meta.test"
                   value="true"
                   data-endpoint="POSTapi-v1-screen"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-screen" style="display: none">
            <input type="radio" name="meta.test"
                   value="false"
                   data-endpoint="POSTapi-v1-screen"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Example: <code>false</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                    <h2 id="kyc-screening-GETapi-v1-status--uuid-">Get KYC screening status</h2>

<p>
</p>

<p>Retrieve the current status of a KYC screening request by UUID. Use this endpoint to poll for screening results after submitting a KYC request. The status will be 'pending' initially, then change to 'approved', 'rejected', or 'error' once the provider completes processing.</p>

<span id="example-requests-GETapi-v1-status--uuid-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8888/api/v1/status/550e8400-e29b-41d4-a716-446655440000" \
    --header "X-API-KEY: Your API key for authentication" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/v1/status/550e8400-e29b-41d4-a716-446655440000"
);

const headers = {
    "X-API-KEY": "Your API key for authentication",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-status--uuid-">
            <blockquote>
            <p>Example response (200, Status retrieved successfully. Status values: pending (processing), approved (verified), rejected (failed verification), error (provider error)):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Status retrieved successfully&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440005&quot;
    },
    &quot;data&quot;: {
        &quot;uuid&quot;: &quot;550e8400-e29b-41d4-a716-446655440000&quot;,
        &quot;status&quot;: &quot;pending&quot;,
        &quot;provider&quot;: &quot;regtank&quot;,
        &quot;provider_reference_id&quot;: &quot;REF123456&quot;,
        &quot;created_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, Example of approved status - verification passed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Status retrieved successfully&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440006&quot;
    },
    &quot;data&quot;: {
        &quot;uuid&quot;: &quot;550e8400-e29b-41d4-a716-446655440000&quot;,
        &quot;status&quot;: &quot;approved&quot;,
        &quot;provider&quot;: &quot;regtank&quot;,
        &quot;provider_reference_id&quot;: &quot;REF123456&quot;,
        &quot;created_at&quot;: &quot;2025-01-15T10:30:00.000000Z&quot;,
        &quot;updated_at&quot;: &quot;2025-01-15T10:35:00.000000Z&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Authentication failed - invalid or missing X-API-KEY header):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 401,
        &quot;message&quot;: &quot;Unauthorized&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440008&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;Unauthorized&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, Profile not found - invalid UUID or profile does not exist):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 404,
        &quot;message&quot;: &quot;Profile not found&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440007&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;Profile not found&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-status--uuid-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-status--uuid-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-status--uuid-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-status--uuid-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-status--uuid-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-status--uuid-" data-method="GET"
      data-path="api/v1/status/{uuid}"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-status--uuid-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-status--uuid-"
                    onclick="tryItOut('GETapi-v1-status--uuid-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-status--uuid-"
                    onclick="cancelTryOut('GETapi-v1-status--uuid-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-status--uuid-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/status/{uuid}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>X-API-KEY</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="X-API-KEY"                data-endpoint="GETapi-v1-status--uuid-"
               value="Your API key for authentication"
               data-component="header">
    <br>
<p>Example: <code>Your API key for authentication</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-status--uuid-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-status--uuid-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>uuid</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="uuid"                data-endpoint="GETapi-v1-status--uuid-"
               value="550e8400-e29b-41d4-a716-446655440000"
               data-component="url">
    <br>
<p>The UUID of the KYC profile returned from the screening request Example: <code>550e8400-e29b-41d4-a716-446655440000</code></p>
            </div>
                    </form>

                <h1 id="ocr-services">OCR Services</h1>

    

                                <h2 id="ocr-services-POSTapi-v1-ocr">Process OCR for identity documents</h2>

<p>
</p>

<p>Extracts text and data from Indonesian identity documents (KTP or Passport) using GlairAI OCR service. This endpoint processes uploaded document images and returns structured data including personal information, document numbers, and validity dates.</p>

<span id="example-requests-POSTapi-v1-ocr">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "http://localhost:8888/api/v1/ocr" \
    --header "Authorization: JWT Bearer token for authentication" \
    --header "Content-Type: multipart/form-data" \
    --header "Accept: application/json" \
    --form "document_type=KTP"\
    --form "options[enhance]=1"\
    --form "options[lang]=id"\
    --form "options[detect_orientation]=1"\
    --form "meta[test]="\
    --form "image=@/private/var/folders/vb/mvc67p5d5jx6n1wbfv9d2gpr0000gn/T/phpif9052gs77vtc1W73fA" </code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/v1/ocr"
);

const headers = {
    "Authorization": "JWT Bearer token for authentication",
    "Content-Type": "multipart/form-data",
    "Accept": "application/json",
};

const body = new FormData();
body.append('document_type', 'KTP');
body.append('options[enhance]', '1');
body.append('options[lang]', 'id');
body.append('options[detect_orientation]', '1');
body.append('meta[test]', '');
body.append('image', document.querySelector('input[name="image"]').files[0]);

fetch(url, {
    method: "POST",
    headers,
    body,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTapi-v1-ocr">
            <blockquote>
            <p>Example response (200, Successfully extracted data from KTP document):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Success&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440030&quot;
    },
    &quot;data&quot;: {
        &quot;fields&quot;: {
            &quot;national_id_number&quot;: &quot;3275012345670001&quot;,
            &quot;full_name&quot;: &quot;BUDI SANTOSO&quot;,
            &quot;place_of_birth&quot;: &quot;JAKARTA&quot;,
            &quot;date_of_birth&quot;: &quot;1990-01-15&quot;,
            &quot;gender&quot;: &quot;male&quot;,
            &quot;address&quot;: &quot;JL. SUDIRMAN NO. 123&quot;,
            &quot;rt_rw&quot;: &quot;001/002&quot;,
            &quot;village&quot;: &quot;MENTENG&quot;,
            &quot;district&quot;: &quot;MENTENG&quot;,
            &quot;religion&quot;: &quot;islam&quot;,
            &quot;marital_status&quot;: &quot;married&quot;,
            &quot;occupation&quot;: &quot;private employee&quot;,
            &quot;citizenship&quot;: &quot;indonesian&quot;,
            &quot;valid_until&quot;: &quot;lifetime&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, Successfully extracted data from Passport document):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Success&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440031&quot;
    },
    &quot;data&quot;: {
        &quot;fields&quot;: {
            &quot;passport_number&quot;: &quot;A12345678&quot;,
            &quot;full_name&quot;: &quot;SITI RAHAYU&quot;,
            &quot;nationality&quot;: &quot;INDONESIA&quot;,
            &quot;date_of_birth&quot;: &quot;1985-05-20&quot;,
            &quot;place_of_birth&quot;: &quot;BANDUNG&quot;,
            &quot;gender&quot;: &quot;female&quot;,
            &quot;issue_date&quot;: &quot;2020-01-15&quot;,
            &quot;expiry_date&quot;: &quot;2025-01-14&quot;
        }
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (400, Invalid document type - only KTP and PASSPORT are supported):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 400,
        &quot;message&quot;: &quot;Unsupported document type&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440034&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;Unsupported document type&quot;
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation error - missing or invalid fields):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 422,
        &quot;message&quot;: &quot;Validation failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440032&quot;
    },
    &quot;errors&quot;: {
        &quot;document_type&quot;: [
            &quot;The document type field is required.&quot;
        ],
        &quot;image&quot;: [
            &quot;The image field is required.&quot;,
            &quot;The image must be a file of type: jpeg, png, jpg, gif, svg.&quot;
        ]
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (503, OCR service unavailable or failed to process document):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 503,
        &quot;message&quot;: &quot;KTP reading failed&quot;,
        &quot;request_id&quot;: &quot;550e8400-e29b-41d4-a716-446655440033&quot;
    },
    &quot;errors&quot;: {
        &quot;error&quot;: &quot;KTP reading failed&quot;
    }
}</code>
 </pre>
    </span>
<span id="execution-results-POSTapi-v1-ocr" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTapi-v1-ocr"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTapi-v1-ocr"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTapi-v1-ocr" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTapi-v1-ocr">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTapi-v1-ocr" data-method="POST"
      data-path="api/v1/ocr"
      data-authed="0"
      data-hasfiles="1"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTapi-v1-ocr', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTapi-v1-ocr"
                    onclick="tryItOut('POSTapi-v1-ocr');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTapi-v1-ocr"
                    onclick="cancelTryOut('POSTapi-v1-ocr');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTapi-v1-ocr"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>api/v1/ocr</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization"                data-endpoint="POSTapi-v1-ocr"
               value="JWT Bearer token for authentication"
               data-component="header">
    <br>
<p>Example: <code>JWT Bearer token for authentication</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTapi-v1-ocr"
               value="multipart/form-data"
               data-component="header">
    <br>
<p>Example: <code>multipart/form-data</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTapi-v1-ocr"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>document_type</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="document_type"                data-endpoint="POSTapi-v1-ocr"
               value="KTP"
               data-component="body">
    <br>
<p>Type of document to process (KTP or PASSPORT) Example: <code>KTP</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>image</code></b>&nbsp;&nbsp;
<small>file</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="file" style="display: none"
                              name="image"                data-endpoint="POSTapi-v1-ocr"
               value=""
               data-component="body">
    <br>
<p>Image file of the document (jpeg, png, jpg, gif, svg). Max size: 2MB Example: <code>/private/var/folders/vb/mvc67p5d5jx6n1wbfv9d2gpr0000gn/T/phpif9052gs77vtc1W73fA</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>options</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Optional OCR processing options</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>enhance</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-ocr" style="display: none">
            <input type="radio" name="options.enhance"
                   value="true"
                   data-endpoint="POSTapi-v1-ocr"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-ocr" style="display: none">
            <input type="radio" name="options.enhance"
                   value="false"
                   data-endpoint="POSTapi-v1-ocr"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Enable image enhancement for better OCR accuracy Example: <code>true</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>lang</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="options.lang"                data-endpoint="POSTapi-v1-ocr"
               value="id"
               data-component="body">
    <br>
<p>Language code for OCR (e.g., 'id' for Indonesian) Example: <code>id</code></p>
                    </div>
                                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>detect_orientation</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-ocr" style="display: none">
            <input type="radio" name="options.detect_orientation"
                   value="true"
                   data-endpoint="POSTapi-v1-ocr"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-ocr" style="display: none">
            <input type="radio" name="options.detect_orientation"
                   value="false"
                   data-endpoint="POSTapi-v1-ocr"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Automatically detect and correct document orientation Example: <code>true</code></p>
                    </div>
                                    </details>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
        <details>
            <summary style="padding-bottom: 10px;">
                <b style="line-height: 2;"><code>meta</code></b>&nbsp;&nbsp;
<small>object</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
<br>
<p>Metadata for the request</p>
            </summary>
                                                <div style="margin-left: 14px; clear: unset;">
                        <b style="line-height: 2;"><code>test</code></b>&nbsp;&nbsp;
<small>boolean</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <label data-endpoint="POSTapi-v1-ocr" style="display: none">
            <input type="radio" name="meta.test"
                   value="true"
                   data-endpoint="POSTapi-v1-ocr"
                   data-component="body"             >
            <code>true</code>
        </label>
        <label data-endpoint="POSTapi-v1-ocr" style="display: none">
            <input type="radio" name="meta.test"
                   value="false"
                   data-endpoint="POSTapi-v1-ocr"
                   data-component="body"             >
            <code>false</code>
        </label>
    <br>
<p>Set to true to return mock data without calling GlairAI (for testing) Example: <code>false</code></p>
                    </div>
                                    </details>
        </div>
        </form>

                <h1 id="system-information">System Information</h1>

    

                                <h2 id="system-information-GETapi-v1-providers">List KYC Providers</h2>

<p>
</p>

<p>Retrieve all available KYC service providers</p>

<span id="example-requests-GETapi-v1-providers">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8888/api/v1/providers" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8888/api/v1/providers"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETapi-v1-providers">
            <blockquote>
            <p>Example response (200, Successful response with provider list):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;meta&quot;: {
        &quot;code&quot;: 200,
        &quot;message&quot;: &quot;Success&quot;,
        &quot;request_id&quot;: &quot;uuid&quot;
    },
    &quot;data&quot;: [
        &quot;regtank&quot;,
        &quot;glair_ai&quot;,
        &quot;test&quot;
    ]
}</code>
 </pre>
    </span>
<span id="execution-results-GETapi-v1-providers" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETapi-v1-providers"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETapi-v1-providers"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETapi-v1-providers" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETapi-v1-providers">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETapi-v1-providers" data-method="GET"
      data-path="api/v1/providers"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETapi-v1-providers', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETapi-v1-providers"
                    onclick="tryItOut('GETapi-v1-providers');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETapi-v1-providers"
                    onclick="cancelTryOut('GETapi-v1-providers');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETapi-v1-providers"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>api/v1/providers</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETapi-v1-providers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETapi-v1-providers"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
