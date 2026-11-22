import{f}from"./2-DrU-BhZA.js";import{f as x}from"./15-BpjtG8xY.js";import{f as v}from"./10-_wOsvybG.js";import{_ as w}from"./pageheader-DS_TsNzx.js";import{S as t}from"./showcodeCard-CNqB0ABy.js";import{_,c as n,e as d,f as l,g as a,w as o,F as r,s as g,C as p,t as b,j as e}from"./index-Dv1CqIWO.js";let h=[{id:1,heading:"Primary",color:"primary",class:""},{id:5,heading:"Secondary",color:"secondary",class:""},{id:6,heading:"Success",color:"success",class:""},{id:7,heading:"Danger",color:"danger",class:""},{id:8,heading:"Warning",color:"warning",class:""},{id:9,heading:"Info",color:"info",class:""},{id:10,heading:"Light",color:"light",class:"text-dark"},{id:11,heading:"Dark",color:"dark",class:"text-white"}],u=[{id:1,heading:"Primary",color:"primary",class:""},{id:5,heading:"Secondary",color:"secondary",class:""},{id:6,heading:"Success",color:"success",class:""},{id:7,heading:"Danger",color:"danger",class:""},{id:8,heading:"Warning",color:"warning",class:""},{id:9,heading:"Info",color:"info",class:""},{id:10,heading:"Light",color:"light",class:"text-dark"},{id:11,heading:"Dark",color:"dark",class:""}],m=[{id:1,heading:"Primary",color:"primary"},{id:2,heading:"Secondary",color:"secondary"},{id:3,heading:"Success",color:"success"},{id:4,heading:"Danger",color:"danger"},{id:5,heading:"Warning",color:"warning"},{id:6,heading:"Info",color:"info"},{id:7,heading:"Orange",color:"orange"},{id:8,heading:"Purple",color:"purple"}],B={script:`
    <span :class="badge bg-{idx.color} {idx.class}" v-for="(idx,index) in badgeData.Badgesdata" :key="index">{{idx.heading}}</span>
   `,data:`
   Badgesdata = [
    { id: 1, heading: "Primary", color: "primary", class: "" },
    { id: 5, heading: "Secondary", color: "secondary", class: "" },
    { id: 6, heading: "Success", color: "success", class: "" },
    { id: 7, heading: "Danger", color: "danger", class: "" },
    { id: 8, heading: "Warning", color: "warning", class: "" },
    { id: 9, heading: "Info", color: "info", class: "" },
    { id: 10, heading: "Light", color: "light", class: "text-dark" },
    { id: 11, heading: "Dark", color: "dark", class: "text-white" },
]
   `},k={script:`
   <span :class="badge rounded-pill bg-{badge.color} {badge.class}" v-for="(badge,index) in badgeData.Badgesdata" :key="index">{{badge.heading}}</span>`,data:`
   Outlinebadgesdata = [
        { id: 1, heading: "Primary", color: "primary", class: "" },
        { id: 5, heading: "Secondary", color: "secondary", class: "" },
        { id: 6, heading: "Success", color: "success", class: "" },
        { id: 7, heading: "Danger", color: "danger", class: "" },
        { id: 8, heading: "Warning", color: "warning", class: "" },
        { id: 9, heading: "Info", color: "info", class: "" },
        { id: 10, heading: "Light", color: "light", class: "text-dark" },
        { id: 11, heading: "Dark", color: "dark", class: "" },
    ]
   `},N={script:`
    <span :class="badge bg-{badge.color}-transparent {badge.class}" v-for="(badge,index) in badgeData.Outlinebadgesdata" :key="index">{{badge.heading}}</span>
   `,data:`
   Outlinebadgesdata = [
        { id: 1, heading: "Primary", color: "primary", class: "" },
        { id: 5, heading: "Secondary", color: "secondary", class: "" },
        { id: 6, heading: "Success", color: "success", class: "" },
        { id: 7, heading: "Danger", color: "danger", class: "" },
        { id: 8, heading: "Warning", color: "warning", class: "" },
        { id: 9, heading: "Info", color: "info", class: "" },
        { id: 10, heading: "Light", color: "light", class: "text-dark" },
        { id: 11, heading: "Dark", color: "dark", class: "" },
    ]
   `},C={script:`
    &lt;span :class="badge rounded-pill bg-{badge.color}-transparent {badge.class}" v-for="(badge,index) in badgeData.Outlinebadgesdata" :key="index">{{badge.heading}}&lt;/span>
    `},D={script:`
    <span :class="badge bg-{badge1.color}-gradient" v-for="(badge1,index) in badgeData.badges1" :key="index">{{badge1.heading}}</span>
    `,data:`
     badges1 = [
        { id: 1, heading: "Primary", color: "primary" },
        { id: 2, heading: "secondary", color: "secondary" },
        { id: 3, heading: "Success", color: "success" },
        { id: 4, heading: "Danger", color: "danger" },
        { id: 5, heading: "Warning", color: "warning" },
        { id: 6, heading: "Info", color: "info" },
        { id: 7, heading: "orange", color: "orange" },
        { id: 8, heading: "purple", color: "purple" },
    ]
    `},P={script:`
     <span :class="badge rounded-pill bg-{badge1.color}-gradient" v-for="(badge1,index) in badgeData.badges1" :key="index">{{badge1.heading}}</span>
    `,data:`
    badges1 = [
        { id: 1, heading: "Primary", color: "primary" },
        { id: 2, heading: "secondary", color: "secondary" },
        { id: 3, heading: "Success", color: "success" },
        { id: 4, heading: "Danger", color: "danger" },
        { id: 5, heading: "Warning", color: "warning" },
        { id: 6, heading: "Info", color: "info" },
        { id: 7, heading: "orange", color: "orange" },
        { id: 8, heading: "purple", color: "purple" },
    ]
    `},S={script:`
    <span :class="badge bg-outline-{badge.color} {badge.class}" v-for="(badge,index) in badgeData.Outlinebadgesdata" :key="index">{{badge.heading}}</span>
    `,data:`
     Outlinebadgesdata = [
        { id: 1, heading: "Primary", color: "primary", class: "" },
        { id: 5, heading: "Secondary", color: "secondary", class: "" },
        { id: 6, heading: "Success", color: "success", class: "" },
        { id: 7, heading: "Danger", color: "danger", class: "" },
        { id: 8, heading: "Warning", color: "warning", class: "" },
        { id: 9, heading: "Info", color: "info", class: "" },
        { id: 10, heading: "Light", color: "light", class: "text-dark" },
        { id: 11, heading: "Dark", color: "dark", class: "" },
    ]
    `},I={script:`
   <span :class="badge rounded-pill bg-outline-{badge.color} {badge.class}" v-for="(badge,index) in badgeData.Outlinebadgesdata" :key="index">{{badge.heading}}</span>
    `,data:`
    Outlinebadgesdata = [
        { id: 1, heading: "Primary", color: "primary", class: "" },
        { id: 5, heading: "Secondary", color: "secondary", class: "" },
        { id: 6, heading: "Success", color: "success", class: "" },
        { id: 7, heading: "Danger", color: "danger", class: "" },
        { id: 8, heading: "Warning", color: "warning", class: "" },
        { id: 9, heading: "Info", color: "info", class: "" },
        { id: 10, heading: "Light", color: "light", class: "text-dark" },
        { id: 11, heading: "Dark", color: "dark", class: "" },
    ]
    `},$={script:`
    <button type="button" class="btn btn-primary my-1 me-2">
                Notifications <span class="badge ms-2 bg-secondary">4</span>
            </button>
            <button type="button" class="btn btn-secondary my-1 me-2">
                Notifications <span class="badge ms-2 bg-primary">7</span>
            </button>
            <button type="button" class="btn btn-success my-1 me-2">
                Notifications <span class="badge ms-2 bg-danger">12</span>
            </button>
            <button type="button" class="btn btn-info my-1 me-2">
                Notifications <span class="badge ms-2 bg-warning">32</span>
            </button>`},E={script:`
    <button type="button" class="btn btn-outline-primary my-1 me-2">
                Notifications <span class="badge ms-2">4</span>
            </button>
            <button type="button" class="btn btn-outline-secondary my-1 me-2">
                Notifications <span class="badge ms-2">7</span>
            </button>
            <button type="button" class="btn btn-outline-success my-1 me-2">
                Notifications <span class="badge ms-2">12</span>
            </button>
            <button type="button" class="btn btn-outline-info my-1 me-2">
                Notifications <span class="badge ms-2">32</span>
            </button>`},O={script:`
    <h1>Example heading <span class="badge bg-primary">New</span></h1>
            <h2>Example heading <span class="badge bg-primary">New</span></h2>
            <h3>Example heading <span class="badge bg-primary">New</span></h3>
            <h4>Example heading <span class="badge bg-primary">New</span></h4>
            <h5>Example heading <span class="badge bg-primary">New</span></h5>
            <h6>Example heading <span class="badge bg-primary">New</span></h6>`},W={script:`
    <button type="button" class="btn btn-primary position-relative">
                        Inbox
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            99+
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary position-relative">
                        Profile
                        <span class="position-absolute top-80 start-100 translate-middle p-2 bg-success border border-light rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </button>
                    <span class="avatar">
                        <img src="/images/faces/2.jpg" alt="img">
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </span>
                    <span class="avatar avatar-rounded">
                        <img src="/images/faces/15.jpg" alt="img">
                        <span class="position-absolute top-80 start-100 translate-middle p-1 bg-success border border-light              rounded-circle">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </span>
                    <span class="avatar avatar-rounded">
                        <img src="/images/faces/10.jpg" alt="img">
                        <span class="position-absolute top-0 start-100 translate-middle badge bg-secondary rounded-pill shadow-lg">1000+
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </span>`},L={script:`
   <div class="d-flex align-items-center gap-5 flex-wrap">
                        <div>
                            <span class="badge bg-outline-secondary custom-badge fs-15 d-inline-flex align-items-center"><i class="ti ti-flame me-1"></i>Hot</span>
                        </div>
                        <div>
                            <span class="icon-badge">
                                <svg class="icon" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000">
                                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" /></svg>
                                <span class="badge rounded-pill bg-success">14</span>
                            </span>
                        </div>
                        <div>
                            <span class="badge border bg-light text-default custom-badge"><i class="fe fe-eye me-2 d-inline-block"></i>13.2k</span>
                        </div>
                        <div>
                            <span class="text-badge">
                                <span class="text fs-18">Inbox</span>
                                <span class="badge rounded-pill bg-success">32</span>
                            </span>
                        </div>
                    </div>`};const z={class:"row"},V={class:"col-xl-12"},j={class:"col-xl-12"},H={class:"row"},F={class:"col-xl-12"},G={class:"col-xl-12"},M={class:"row"},T={class:"col-xl-12"},U={class:"col-xl-12"},q={class:"row"},A={class:"col-xl-12"},J={class:"col-xl-12"},K={class:"row"},Q={class:"col-xl-12"},R={class:"col-xl-12"},X={class:"row"},Y={class:"col-xl-12"},Z={class:"row"},aa={class:"col-xl-12"},sa={class:"col-xl-12"},ea={class:"col-xl-12"},na={__name:"badge",setup(da){const y={title:"Ui Elements",currentpage:"Badge",activepage:"Badge"};return(ia,c)=>(d(),n(r,null,[l(w,{propData:y}),a("div",z,[a("div",V,[l(t,{title:"Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:B},{default:o(()=>[(d(!0),n(r,null,g(h,(s,i)=>(d(),n("span",{class:p(`badge bg-${s.color} ${s.class}`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])]),a("div",j,[l(t,{title:"Light Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:N},{default:o(()=>[(d(!0),n(r,null,g(u,(s,i)=>(d(),n("span",{class:p(`badge bg-${s.color}-transparent ${s.class}`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])])]),a("div",H,[a("div",F,[l(t,{title:"Pill Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:k},{default:o(()=>[(d(!0),n(r,null,g(h,(s,i)=>(d(),n("span",{class:p(`badge rounded-pill bg-${s.color} ${s.class}`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])]),a("div",G,[l(t,{title:"Light Pill Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:C},{default:o(()=>[(d(!0),n(r,null,g(u,(s,i)=>(d(),n("span",{class:p(`badge rounded-pill bg-${s.color}-transparent ${s.class}`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])])]),a("div",M,[a("div",T,[l(t,{title:"Gradient Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:D},{default:o(()=>[(d(!0),n(r,null,g(m,(s,i)=>(d(),n("span",{class:p(`badge bg-${s.color}-gradient`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])]),a("div",U,[l(t,{title:"Gradient Pill Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:P},{default:o(()=>[(d(!0),n(r,null,g(m,(s,i)=>(d(),n("span",{class:p(`badge rounded-pill bg-${s.color}-gradient`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])])]),a("div",q,[a("div",A,[l(t,{title:"Outline Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:S},{default:o(()=>[(d(!0),n(r,null,g(u,(s,i)=>(d(),n("span",{class:p(`badge bg-outline-${s.color} ${s.class}`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])]),a("div",J,[l(t,{title:"Outline Pill Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:I},{default:o(()=>[(d(!0),n(r,null,g(u,(s,i)=>(d(),n("span",{class:p(`badge rounded-pill bg-outline-${s.color} ${s.class}`),key:i},b(s.heading),3))),128))]),_:1},8,["code"])])]),a("div",K,[a("div",Q,[l(t,{title:"Buttons With Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:$},{default:o(()=>c[0]||(c[0]=[a("button",{type:"button",class:"btn btn-primary my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2 bg-secondary"},"4")],-1),a("button",{type:"button",class:"btn btn-secondary my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2 bg-primary"},"7")],-1),a("button",{type:"button",class:"btn btn-success my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2 bg-danger"},"12")],-1),a("button",{type:"button",class:"btn btn-info my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2 bg-warning"},"32")],-1)])),_:1,__:[0]},8,["code"])]),a("div",R,[l(t,{title:"Outline Button Badges",customCardBodyClass:"d-flex flex-wrap gap-2",code:E},{default:o(()=>c[1]||(c[1]=[a("button",{type:"button",class:"btn btn-outline-primary my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2"},"4")],-1),a("button",{type:"button",class:"btn btn-outline-secondary my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2"},"7")],-1),a("button",{type:"button",class:"btn btn-outline-success my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2"},"12")],-1),a("button",{type:"button",class:"btn btn-outline-info my-1 me-2"},[e(" Notifications "),a("span",{class:"badge ms-2"},"32")],-1)])),_:1,__:[1]},8,["code"])])]),a("div",X,[a("div",Y,[a("div",Z,[a("div",aa,[l(t,{title:"Positioned Badges",customCardBodyClass:"d-flex flex-wrap gap-4 pe-4",code:W},{default:o(()=>c[2]||(c[2]=[a("button",{type:"button",class:"btn btn-primary position-relative"},[e(" Inbox "),a("span",{class:"position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"},[e(" 99+ "),a("span",{class:"visually-hidden"},"unread messages")])],-1),a("button",{type:"button",class:"btn btn-secondary position-relative"},[e(" Profile "),a("span",{class:"position-absolute top-80 start-100 translate-middle p-2 bg-success border border-light rounded-circle"},[a("span",{class:"visually-hidden"},"New alerts")])],-1),a("span",{class:"avatar"},[a("img",{src:f,alt:"img"}),a("span",{class:"position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"},[a("span",{class:"visually-hidden"},"New alerts")])],-1),a("span",{class:"avatar avatar-rounded"},[a("img",{src:x,alt:"img"}),a("span",{class:"position-absolute top-80 start-100 translate-middle p-1 bg-success border border-light rounded-circle"},[a("span",{class:"visually-hidden"},"New alerts")])],-1),a("span",{class:"avatar avatar-rounded"},[a("img",{src:v,alt:"img"}),a("span",{class:"position-absolute top-0 start-100 translate-middle badge bg-secondary rounded-pill shadow-lg"},[e("1000+ "),a("span",{class:"visually-hidden"},"New alerts")])],-1)])),_:1,__:[2]},8,["code"])]),a("div",sa,[l(t,{title:"Custom Badges",code:L},{default:o(()=>c[3]||(c[3]=[a("div",{class:"d-flex align-items-center gap-5 flex-wrap"},[a("div",null,[a("span",{class:"badge bg-outline-secondary custom-badge fs-15 d-inline-flex align-items-center"},[a("i",{class:"ti ti-flame me-1"}),e("Hot")])]),a("div",null,[a("span",{class:"icon-badge"},[a("svg",{class:"icon",xmlns:"http://www.w3.org/2000/svg",height:"24px",viewBox:"0 0 24 24",width:"24px",fill:"#000000"},[a("path",{d:"M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"})]),a("span",{class:"badge rounded-pill bg-success"},"14")])]),a("div",null,[a("span",{class:"badge border bg-light text-default custom-badge"},[a("i",{class:"fe fe-eye me-2 d-inline-block"}),e("13.2k")])]),a("div",null,[a("span",{class:"text-badge"},[a("span",{class:"text fs-18"},"Inbox"),a("span",{class:"badge rounded-pill bg-success"},"32")])])],-1)])),_:1,__:[3]},8,["code"])])])]),a("div",ea,[l(t,{title:"Headings",code:O},{default:o(()=>c[4]||(c[4]=[a("h1",null,[e("Example heading "),a("span",{class:"badge bg-primary"},"New")],-1),a("h2",null,[e("Example heading "),a("span",{class:"badge bg-primary"},"New")],-1),a("h3",null,[e("Example heading "),a("span",{class:"badge bg-primary"},"New")],-1),a("h4",null,[e("Example heading "),a("span",{class:"badge bg-primary"},"New")],-1),a("h5",null,[e("Example heading "),a("span",{class:"badge bg-primary"},"New")],-1),a("h6",null,[e("Example heading "),a("span",{class:"badge bg-primary"},"New")],-1)])),_:1,__:[4]},8,["code"])])])],64))}},pa=_(na,[["__scopeId","data-v-951fff35"]]);export{pa as default};
