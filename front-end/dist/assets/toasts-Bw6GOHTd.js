import{d as V,r as z,o as x,c as Y,e as $,f as r,g as t,w as u,al as m,j as S,F as _,_ as H}from"./index-0CTb4OVm.js";import{l as v}from"./index-BkilMoKJ.js";/* empty css              */import{S as b}from"./showcodeCard-B7BXpv2j.js";import{_ as A}from"./pageheader-7gVBv3pS.js";let L={script:`
     <button type="button" class="btn btn-primary btn-wave" id="liveToastBtn" @click="showliveToast">Show live
                    toast</button>
                <div class="toast-container position-fixed top-0 end-0 p-3">
                    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header text-default">
                            <img class="bd-placeholder-img rounded me-2" src="/images/brand-logos/favicon.ico" alt="...">
                            <strong class="me-auto">Vyzor</strong>
                            <small>11 mins ago</small>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Hello, world! This is a toast message.
                        </div>
                    </div>
                </div>`},E={script:`
    <div class="toast align-items-center text-bg-primary border-0 fade show mb-4" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            Hello, world! This is the Primary toast message.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
                <div class="toast align-items-center text-bg-secondary border-0 fade show mb-4" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            Hello, world! This is the Secondary toast.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
                <div class="toast align-items-center text-bg-warning border-0 fade show mb-4" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            Hello, world! This is the Warning toast message.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
                <div class="toast align-items-center text-bg-info border-0 fade show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            Hello, world! This is the info toast message.
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>`},R={script:` <div class="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header text-default">
                        <img class="bd-placeholder-img rounded me-2" src="/images/brand-logos/favicon.ico" alt="...">

                        <strong class="me-auto">Vyzor</strong>
                        <small>11 mins ago</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Hello, world! This is a toast message.
                    </div>
                </div>`},P={script:`<div class="toast-container position-static">
                    <div class="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header text-default">
                            <img class="bd-placeholder-img rounded me-2" src="/images/brand-logos/favicon.ico" alt="...">

                            <strong class="me-auto">Vyzor</strong>
                            <small class="text-muted">just now</small>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            See? Just like this.
                        </div>
                    </div>
                    <div class="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header text-default">
                            <img class="bd-placeholder-img rounded me-2" src="/images/brand-logos/favicon.ico" alt="...">

                            <strong class="me-auto">Vyzor</strong>
                            <small class="text-muted">2 seconds ago</small>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Heads up, toasts will stack automatically
                        </div>
                    </div>
                </div>`},U={script:` <div class="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header text-default">
                        <img class="bd-placeholder-img rounded me-2" src="../assets/images/brand-logos/favicon.ico" alt="...">

                        <strong class="me-auto">Vyzor</strong>
                        <small class="text-muted">11 mins ago</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Hello, world! This is a toast message.
                    </div>
                </div>`},D={script:`  <div class="btn-list">
                <button v-on:click="successToast(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        true,
                        true,
                        'bounce',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-success-light me-2 btn-wave" id="successToastBtn">success</button>
                    <button v-on:click="warningToast(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        true,
                        true,
                        'bounce',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-warning-light me-2 btn-wave" id="warningToastBtn">warning</button>
                    <button v-on:click="infoToast(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        true,
                        true,
                        'bounce',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-info-light me-2 btn-wave" id="infoToastBtn">info</button>
                    <button v-on:click="errorToast(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        true,
                        true,
                        'bounce',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-danger-light me-2 btn-wave" id="dangerToastBtn">danger</button>
            </div>`},N={script:`&lt;div class="btn-list">
    &lt;button v-on:click="successToast(
        '&lt;img class=' bd-placeholder-img rounded me-2 ' src='+BASE_URL+'images/brand-logos/toggle-dark.png alt='...'>' +
        '&lt;strong class=' me-auto'>Vyzor&lt;/strong>' +
        '&lt;div class='toast-body'>Your toast message here.&lt;/div>',
        true,
        'auto',
        true,
        true,
        'bounce',
        1000,
        'top-right'
    )" type="button" class="btn btn-success-light me-2 btn-wave" id="successToastBtn">success&lt;/button>
    &lt;button v-on:click="warningToast(
        '&lt;img class=' bd-placeholder-img rounded me-2 ' src='+BASE_URL+'images/brand-logos/toggle-dark.png alt='...'>' +
        '&lt;strong class=' me-auto'>Vyzor&lt;/strong>' +
        '&lt;div class='toast-body'>Your toast message here.&lt;/div>',
        true,
        'auto',
        true,
        true,
        'bounce',
        1000,
        'top-right'
    )" type="button" class="btn btn-warning-light me-2 btn-wave" id="warningToastBtn">warning&lt;/button>
    &lt;button v-on:click="infoToast(
        '&lt;img class=' bd-placeholder-img rounded me-2 ' src='+BASE_URL+'images/brand-logos/toggle-dark.png alt='...'>' +
        '&lt;strong class=' me-auto'>Vyzor&lt;/strong>' +
        '&lt;div class='toast-body'>Your toast message here.&lt;/div>',
        true,
        'auto',
        true,
        true,
        'bounce',
        1000,
        'top-right'
    )" type="button" class="btn btn-info-light me-2 btn-wave" id="infoToastBtn">info&lt;/button>
    &lt;button v-on:click="errorToast(
        '&lt;img class=' bd-placeholder-img rounded me-2 ' src='+BASE_URL+'images/brand-logos/toggle-dark.png alt='...'>' +
        '&lt;strong class=' me-auto'>Vyzor&lt;/strong>' +
        '&lt;div class='toast-body'>Your toast message here.&lt;/div>',
        true,
        'auto',
        true,
        true,
        'bounce',
        1000,
        'top-right'
    )" type="button" class="btn btn-danger-light me-2 btn-wave" id="dangerToastBtn">danger&lt;/button>
&lt;/div>`},F={script:`div class="btn-list">
                    <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'top-right', 0, false, true
                    )" type="button" class="btn btn-secondary me-2 btn-wave" id="solidsecondaryToastBtn">Newest
                        on
                        Top</button>
                    <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        true,
                        true,
                        'bounce',
                        1000,
                        'top-right', 3000, false, false
                    )" type="button" class="btn btn-warning me-2 btn-wave" id="solidwarningToastBtn">Delay</button>
                    <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'top-right', 0, true, false
                    )" type="button" class="btn btn-info me-2 btn-wave" id="solidinfoToastBtn">Close on
                        Click</button>
                    <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-success me-2 btn-wave" id="solidsuccessToastBtn">Disable
                        Auto
                        Close</button>
                    <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        true,
                        false,
                        'zoom',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="middlerightToastBtn">Show
                        ProgressBar</button>
                        </div>`},j={script:`            <div class="btn-list">
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'top-left'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="topleftToastBtn">Top
                    Left</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'top-center'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="topcenterToastBtn">Top
                    Center</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="toprightToastBtn">Top
                    Right</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'bottom-left'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="bottomleftToastBtn">Bottom
                    Left</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'bottom-center'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="bottomcenterToastBtn">Bottom
                    Center</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'bounce',
                        1000,
                        'bottom-right'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="bottomrightToastBtn">Bottom
                    Right</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'flip',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="middleleftToastBtn">Flip
                    Animation</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'slide',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="middlecenterToastBtn">Slide
                    Animation</button>
                <button v-on:click="toster(
                        <img class=' bd-placeholder-img rounded me-2 ' src='/images/brand-logos/toggle-dark.png' alt='...'> +
                        '<strong class=' me-auto'>Vyzor</strong>' +
                        '<div class='toast-body'>Your toast message here.</div>',
                        true,
                        'auto',
                        false,
                        true,
                        'zoom',
                        1000,
                        'top-right'
                    )" type="button" class="btn btn-outline-primary me-2 btn-wave" id="middlerightToastBtn">Zoom
                    Animation</button>
            </div>`};const O={class:"row"},J={class:"col-xl-12"},Z={class:"col-xl-12"},q={class:"col-xl-12"},I={class:"row"},M={class:"col-xl-6"},W={class:"btn-list"},G={class:"col-xl-6"},K={class:"btn-list"},Q={class:"row"},X={class:"col-xl-12"},tt={class:"btn-list"},st=V({__name:"toasts",setup(ot){const p={title:"Ui Elements",currentpage:"Toasts",activepage:"Toasts"},e=z("");x(()=>{e.value="/"});function a(l,s,o,i,n,d,g,c,k=1e3,C=!1,B=!1){v.success(String(l),{theme:o,dangerouslyHTMLString:s,icon:i,hideProgressBar:n,transition:d,autoClose:g,position:c,delay:k,closeOnClick:C,newestOnTop:B||!0})}function y(l,s,o,i,n,d,g,c){v.success(String(l),{theme:o,dangerouslyHTMLString:s,icon:i,hideProgressBar:n,transition:d,autoClose:g,position:c})}function h(l,s,o,i,n,d,g,c){v.info(String(l),{theme:o,dangerouslyHTMLString:s,icon:i,hideProgressBar:n,transition:d,autoClose:g,position:c})}function f(l,s,o,i,n,d,g,c){v.warning(String(l),{theme:o,dangerouslyHTMLString:s,icon:i,hideProgressBar:n,transition:d,autoClose:g,position:c})}function w(l,s,o,i,n,d,g,c){v.error(String(l),{theme:o,dangerouslyHTMLString:s,icon:i,hideProgressBar:n,transition:d,autoClose:g,position:c})}function T(l){l.currentTarget.nextElementSibling?.querySelector("#liveToast")?.classList.add("show")}return(l,s)=>($(),Y(_,null,[r(A,{propData:p}),t("div",O,[t("div",J,[r(b,{title:"Live example",code:L},{default:u(()=>[t("button",{type:"button",class:"btn btn-primary btn-wave",id:"liveToastBtn",onClick:T},"Show live toast"),s[18]||(s[18]=t("div",{class:"toast-container position-fixed top-0 end-0 p-3"},[t("div",{id:"liveToast",class:"toast",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"toast-header text-default"},[t("img",{class:"bd-placeholder-img rounded me-1",src:m,alt:"..."}),t("strong",{class:"me-auto"},"Vyzor"),t("small",null,"11 mins ago"),t("button",{type:"button",class:"btn-close","data-bs-dismiss":"toast","aria-label":"Close"})]),t("div",{class:"toast-body"}," Hello, world! This is a toast message. ")])],-1))]),_:1,__:[18]},8,["code"]),r(b,{title:"Color schemes",code:E},{default:u(()=>s[19]||(s[19]=[t("div",{class:"toast align-items-center text-bg-primary border-0 fade show mb-4",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"d-flex"},[t("div",{class:"toast-body"}," Hello, world! This is the Primary toast message. "),t("button",{type:"button",class:"btn-close btn-close-white me-2 m-auto","data-bs-dismiss":"toast","aria-label":"Close"})])],-1),t("div",{class:"toast align-items-center text-bg-secondary border-0 fade show mb-4",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"d-flex"},[t("div",{class:"toast-body"}," Hello, world! This is the Secondary toast. "),t("button",{type:"button",class:"btn-close btn-close-white me-2 m-auto","data-bs-dismiss":"toast","aria-label":"Close"})])],-1),t("div",{class:"toast align-items-center text-bg-success border-0 fade show mb-4",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"d-flex"},[t("div",{class:"toast-body"}," Hello, world! This is the Success toast message. "),t("button",{type:"button",class:"btn-close btn-close-white me-2 m-auto","data-bs-dismiss":"toast","aria-label":"Close"})])],-1),t("div",{class:"toast align-items-center text-bg-info border-0 fade show",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"d-flex"},[t("div",{class:"toast-body"}," Hello, world! This is the info toast message. "),t("button",{type:"button",class:"btn-close btn-close-white me-2 m-auto","data-bs-dismiss":"toast","aria-label":"Close"})])],-1)])),_:1,__:[19]},8,["code"])]),t("div",Z,[r(b,{title:"Basic example",code:R},{default:u(()=>s[20]||(s[20]=[t("div",{class:"toast fade show",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"toast-header text-default"},[t("img",{class:"bd-placeholder-img rounded me-1",src:m,alt:"..."}),t("strong",{class:"me-auto"},"Vyzor"),t("small",null,"11 mins ago"),t("button",{type:"button",class:"btn-close","data-bs-dismiss":"toast","aria-label":"Close"})]),t("div",{class:"toast-body"}," Hello, world! This is a toast message. ")],-1)])),_:1,__:[20]},8,["code"]),r(b,{title:"Stacking",code:P},{default:u(()=>s[21]||(s[21]=[t("div",{class:"toast-container position-static"},[t("div",{class:"toast fade show",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"toast-header text-default"},[t("img",{class:"bd-placeholder-img rounded me-1",src:m,alt:"..."}),t("strong",{class:"me-auto"},"Vyzor"),t("small",{class:"text-muted"},"just now"),t("button",{type:"button",class:"btn-close","data-bs-dismiss":"toast","aria-label":"Close"})]),t("div",{class:"toast-body"}," See? Just like this. ")]),t("div",{class:"toast fade show",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"toast-header text-default"},[t("img",{class:"bd-placeholder-img rounded me-1",src:m,alt:"..."}),t("strong",{class:"me-auto"},"Vyzor"),t("small",{class:"text-muted"},"2 seconds ago"),t("button",{type:"button",class:"btn-close","data-bs-dismiss":"toast","aria-label":"Close"})]),t("div",{class:"toast-body"}," Heads up, toasts will stack automatically ")])],-1)])),_:1,__:[21]},8,["code"])]),t("div",q,[r(b,{title:"Translucent",code:U},{default:u(()=>s[22]||(s[22]=[t("div",{class:"toast fade show",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"toast-header text-default"},[t("img",{class:"bd-placeholder-img rounded me-1",src:m,alt:"..."}),t("strong",{class:"me-auto"},"Vyzor"),t("small",{class:"text-muted"},"11 mins ago"),t("button",{type:"button",class:"btn-close","data-bs-dismiss":"toast","aria-label":"Close"})]),t("div",{class:"toast-body"}," Hello, world! This is a toast message. ")],-1)])),_:1,__:[22]},8,["code"]),r(b,{title:" Custom content",code:D},{default:u(()=>s[23]||(s[23]=[t("div",{class:"toast align-items-center fade show mb-3",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"d-flex"},[t("div",{class:"toast-body"}," Hello, world! This is a toast message. "),t("button",{type:"button",class:"btn-close me-2 m-auto","data-bs-dismiss":"toast","aria-label":"Close"})])],-1),t("div",null,[t("span",{class:"my-4 text-muted"}," Alternatively, you can also add additional controls and components to toasts. ")],-1),t("div",{class:"toast fade show mt-2",role:"alert","aria-live":"assertive","aria-atomic":"true"},[t("div",{class:"toast-body"},[S(" Hello, world! This is a toast message. "),t("div",{class:"mt-2 pt-2 border-top"},[t("button",{type:"button",class:"btn btn-primary btn-sm btn-wave me-1"},"Take action"),t("button",{type:"button",class:"btn btn-secondary btn-sm btn-wave","data-bs-dismiss":"toast"},"Close")])])],-1)])),_:1,__:[23]},8,["code"])])]),t("div",I,[t("div",M,[r(b,{title:"Toast Variants",code:N},{default:u(()=>[t("div",W,[t("button",{onClick:s[0]||(s[0]=o=>y(`<img class='bd-placeholder-img rounded me-2 toast-logo-img' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!0,!0,"bounce",1e3,"top-right")),type:"button",class:"btn btn-success-light me-2 btn-wave",id:"successToastBtn"},"success"),t("button",{onClick:s[1]||(s[1]=o=>f(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!0,!0,"bounce",1e3,"top-right")),type:"button",class:"btn btn-warning-light me-2 btn-wave",id:"warningToastBtn"},"warning"),t("button",{onClick:s[2]||(s[2]=o=>h(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!0,!0,"bounce",1e3,"top-right")),type:"button",class:"btn btn-info-light me-2 btn-wave",id:"infoToastBtn"},"info"),t("button",{onClick:s[3]||(s[3]=o=>w(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!0,!0,"bounce",1e3,"top-right")),type:"button",class:"btn btn-danger-light me-2 btn-wave",id:"dangerToastBtn"},"danger")])]),_:1},8,["code"])]),t("div",G,[r(b,{title:"Toast Options",code:F},{default:u(()=>[t("div",K,[t("button",{onClick:s[4]||(s[4]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"top-right",0,!1,!0)),type:"button",class:"btn btn-secondary me-2 btn-wave",id:"solidsecondaryToastBtn"},"Newest on Top"),t("button",{onClick:s[5]||(s[5]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!0,!0,"bounce",1e3,"top-right",3e3,!1,!1)),type:"button",class:"btn btn-warning me-2 btn-wave",id:"solidwarningToastBtn"},"Delay"),t("button",{onClick:s[6]||(s[6]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"top-right",0,!0,!1)),type:"button",class:"btn btn-info me-2 btn-wave",id:"solidinfoToastBtn"},"Close on Click"),t("button",{onClick:s[7]||(s[7]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"top-right")),type:"button",class:"btn btn-success me-2 btn-wave",id:"solidsuccessToastBtn"},"Disable Auto Close"),t("button",{onClick:s[8]||(s[8]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!0,!1,"zoom",1e3,"top-right")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"middlerightToastBtn"},"Show ProgressBar")])]),_:1},8,["code"])])]),t("div",Q,[t("div",X,[r(b,{title:"Toast Placements",code:j},{default:u(()=>[t("div",tt,[t("button",{onClick:s[9]||(s[9]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"top-left")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"topleftToastBtn"},"Top Left"),t("button",{onClick:s[10]||(s[10]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"top-center")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"topcenterToastBtn"},"Top Center"),t("button",{onClick:s[11]||(s[11]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"top-right")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"toprightToastBtn"},"Top Right"),t("button",{onClick:s[12]||(s[12]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"bottom-left")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"bottomleftToastBtn"},"Bottom Left"),t("button",{onClick:s[13]||(s[13]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"bottom-center")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"bottomcenterToastBtn"},"Bottom Center"),t("button",{onClick:s[14]||(s[14]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"bounce",1e3,"bottom-right")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"bottomrightToastBtn"},"Bottom Right"),t("button",{onClick:s[15]||(s[15]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"flip",1e3,"top-right")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"middleleftToastBtn"},"Flip Animation"),t("button",{onClick:s[16]||(s[16]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"slide",1e3,"top-right")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"middlecenterToastBtn"},"Slide Animation"),t("button",{onClick:s[17]||(s[17]=o=>a(`<img class=' bd-placeholder-img rounded me-2 toast-logo-img ' src='${e.value}images/brand-logos/toggle-logo.png' alt='...'><strong class=' me-auto'>Vyzor</strong><div class='toast-body'>Your toast message here.</div>`,!0,"auto",!1,!0,"zoom",1e3,"top-right")),type:"button",class:"btn btn-outline-primary me-2 btn-wave",id:"middlerightToastBtn"},"Zoom Animation")])]),_:1},8,["code"])])])],64))}}),nt=H(st,[["__scopeId","data-v-9206cfd5"]]);export{nt as default};
