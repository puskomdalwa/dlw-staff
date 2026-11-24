import{_ as h}from"./1-D00sKJHD.js";import{f}from"./3-BNysva2A.js";import{f as v}from"./6-B4-aqYQy.js";import{f as b}from"./15-BpjtG8xY.js";import{f as y}from"./13-DjOIqxVg.js";import{S as c}from"./showcodeCard-Zq62hg10.js";import{_ as w}from"./pageheader-CAxtkOCL.js";import{_ as k,c as i,e as a,f as o,g as t,w as n,F as u,s as p,C as r,t as d,j as e}from"./index-B0X1_r70.js";let _={script:`
     <ul class="list-group">
                <li class="list-group-item" v-for="(idx,index) in listData.BasicButtons" :key="index">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-sm">
                            <img :src="idx.src" alt="img">
                        </span>
                        <div class="ms-2 fw-medium">
                            {{idx.text}}
                        </div>
                    </div>
                </li>
            </ul>`,data:`
             BasicButtons = [
        { id: 1, src: "/images/faces/1.jpg", text: "Alicia Sierra" },
        { id: 2, src: "/images/faces/3.jpg", text: "Samantha Mery" },
        { id: 3, src: "/images/faces/6.jpg", text: "Juliana Pena" },
        { id: 4, src: "/images/faces/15.jpg", text: "Adam Smith" },
        { id: 5, src: "/images/faces/13.jpg", text: "Farhaan Amhed" },
    ]
            `},A={script:`
     <ul class="list-group">
                <li :class="list-group-item {idx.class1}" v-for="(idx,index) in listData.ActiveButtons" :key="index">
                    <div class="d-flex align-items-center">
                        <div>
                            <span class="fs-15">
                                <i :class="idx.class"></i>
                            </span>
                        </div>
                        <div class="ms-2">
                            {{idx.text}}
                        </div>
                    </div>
                </li>
            </ul>`,data:`
        ActiveButtons = [
        { id: 1, class: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M216,116.69V216H152V152H104v64H40V116.69l82.34-82.35a8,8,0,0,1,11.32,0Z" opacity="0.2"></path><path d="M240,208H224V136l2.34,2.34A8,8,0,0,0,237.66,127L139.31,28.68a16,16,0,0,0-22.62,0L18.34,127a8,8,0,0,0,11.32,11.31L32,136v72H16a8,8,0,0,0,0,16H240a8,8,0,0,0,0-16ZM48,120l80-80,80,80v88H160V152a8,8,0,0,0-8-8H104a8,8,0,0,0-8,8v56H48Zm96,88H112V160h32Z"></path></svg>', text: "Home", class1: "active",class2:'white' },
        { id: 2, class: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,147.81,56,112a72,72,0,0,1,144,0c0,35.82,8.3,56.6,14.9,68A8,8,0,0,1,208,192Z" opacity="0.2"></path><path d="M224,71.1a8,8,0,0,1-10.78-3.42,94.13,94.13,0,0,0-33.46-36.91,8,8,0,1,1,8.54-13.54,111.46,111.46,0,0,1,39.12,43.09A8,8,0,0,1,224,71.1ZM35.71,72a8,8,0,0,0,7.1-4.32A94.13,94.13,0,0,1,76.27,30.77a8,8,0,1,0-8.54-13.54A111.46,111.46,0,0,0,28.61,60.32,8,8,0,0,0,35.71,72Zm186.1,103.94A16,16,0,0,1,208,200H167.2a40,40,0,0,1-78.4,0H48a16,16,0,0,1-13.79-24.06C43.22,160.39,48,138.28,48,112a80,80,0,0,1,160,0C208,138.27,212.78,160.38,221.81,175.94ZM150.62,200H105.38a24,24,0,0,0,45.24,0ZM208,184c-10.64-18.27-16-42.49-16-72a64,64,0,0,0-128,0c0,29.52-5.38,53.74-16,72Z"></path></svg>', text: "Notifications", class1: "" ,class2:'primary'},
        { id: 3, class: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M208,128v72a8,8,0,0,1-8,8H56a8,8,0,0,1-8-8V128Z" opacity="0.2"></path><path d="M216,72H180.92c.39-.33.79-.65,1.17-1A29.53,29.53,0,0,0,192,49.57,32.62,32.62,0,0,0,158.44,16,29.53,29.53,0,0,0,137,25.91a54.94,54.94,0,0,0-9,14.48,54.94,54.94,0,0,0-9-14.48A29.53,29.53,0,0,0,97.56,16,32.62,32.62,0,0,0,64,49.57,29.53,29.53,0,0,0,73.91,71c.38.33.78.65,1.17,1H40A16,16,0,0,0,24,88v32a16,16,0,0,0,16,16v64a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V136a16,16,0,0,0,16-16V88A16,16,0,0,0,216,72ZM149,36.51a13.69,13.69,0,0,1,10-4.5h.49A16.62,16.62,0,0,1,176,49.08a13.69,13.69,0,0,1-4.5,10c-9.49,8.4-25.24,11.36-35,12.4C137.7,60.89,141,45.5,149,36.51Zm-64.09.36A16.63,16.63,0,0,1,96.59,32h.49a13.69,13.69,0,0,1,10,4.5c8.39,9.48,11.35,25.2,12.39,34.92-9.72-1-25.44-4-34.92-12.39a13.69,13.69,0,0,1-4.5-10A16.6,16.6,0,0,1,84.87,36.87ZM40,88h80v32H40Zm16,48h64v64H56Zm144,64H136V136h64Zm16-80H136V88h80v32Z"></path></svg>', text: "Sent Messages", class1: "",class2:'primary' },
        { id: 4, class: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M192,96a64,64,0,1,1-64-64A64,64,0,0,1,192,96Z" opacity="0.2"></path><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"></path></svg>', text: "New Requests", class1: "",class2:'primary' },
        { id: 5, class: '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#000000" viewBox="0 0 256 256"><path d="M200,56V208a8,8,0,0,1-8,8H64a8,8,0,0,1-8-8V56Z" opacity="0.2"></path><path d="M216,48H176V40a24,24,0,0,0-24-24H104A24,24,0,0,0,80,40v8H40a8,8,0,0,0,0,16h8V208a16,16,0,0,0,16,16H192a16,16,0,0,0,16-16V64h8a8,8,0,0,0,0-16ZM96,40a8,8,0,0,1,8-8h48a8,8,0,0,1,8,8v8H96Zm96,168H64V64H192ZM112,104v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Zm48,0v64a8,8,0,0,1-16,0V104a8,8,0,0,1,16,0Z"></path></svg>', text: "Deleted Messages", class1: "" ,class2:'primary'},
    ]
        `},L={script:`
    <ul class="list-group">
                    <li class="list-group-item disabled" aria-disabled="true">A disabled item meant to be disabled
                    </li>
                    <li class="list-group-item">Simply dummy text of the printing</li>
                    <li class="list-group-item">There are many variations of passages</li>
                    <li class="list-group-item">All the Lorem Ipsum generators</li>
                    <li class="list-group-item">Written in 45 BC. This book is a treatise on the theory</li>
                </ul>`},B={script:`
    <ul class="list-group list-group-flush">
                <li class="list-group-item fw-medium"><i class="bi bi-envelope align-middle me-2 text-muted"></i>Asish Trivedhi<span class="ms-1 text-muted fw-normal d-inline-block">(+1023-84534)</span></li>
                <li class="list-group-item fw-medium"><i class="bi bi-tiktok align-middle me-2 text-muted"></i>Alezander Russo<span class="ms-1 text-muted fw-normal d-inline-block">(+7546-12342)</span></li>
                <li class="list-group-item fw-medium"><i class="bi bi-whatsapp align-middle me-2 text-muted"></i>Karem Smith<span class="ms-1 text-muted fw-normal d-inline-block">(+9944-56632)</span></li>
                <li class="list-group-item fw-medium"><i class="bi bi-facebook align-middle me-2 text-muted"></i>Melissa Brien<span class="ms-1 text-muted fw-normal d-inline-block">(+1023-34323)</span></li>
                <li class="list-group-item fw-medium"><i class="bi bi-instagram align-middle me-2 text-muted"></i>Kamala Harris<span class="ms-1 text-muted fw-normal d-inline-block">(+91-63421)</span></li>
            </ul>`},H={script:`
    <div class="list-group">
                <a href="javascript:void(0);" :class="list-group-item list-group-item-action {idx.class1}" aria-current="true" v-for="(idx,index) in listData.LinksButtons" :key="index">
                    <div class="d-flex align-items-center">
                        <div>
                            <span :class="avatar avatar-xs bg-{idx.class} text-{idx.color} avatar-rounded">
                                {{idx.text1}}
                            </span>
                        </div>
                        <div class="ms-2">{{idx.text}}</div>
                    </div>
                </a>
            </div>`,data:`
         LinksButtons= [
        { id: 1, class: "white", text: "California", class1: "active", text1: "C", color: "default" },
        { id: 2, class: "secondary", text: "New Jersey", class1: "", text1: "N", color: "" },
        { id: 3, class: "info", text: "Los Angeles", class1: "", text1: "L", color: "" },
        { id: 4, class: "warning", text: "Miami Florida", class1: "", text1: "M", color: "" },
        { id: 5, class: "success", text: "Washington D.C", class1: "disabled", text1: "W", color: "" },
    ]
        `},C={script:`
    <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action active" aria-current="true">Simply dummy text of the printing<span class="badge float-end bg-primary">243</span></button>
                    <button type="button" class="list-group-item list-group-item-action">There are many variations of passages<span class="badge float-end bg-secondary-transparent">35</span></button>
                    <button type="button" class="list-group-item list-group-item-action">All the Lorem Ipsum generators<span class="badge float-end bg-info-transparent">132</span></button>
                    <button type="button" class="list-group-item list-group-item-action">All the Lorem Ipsum generators<span class="badge float-end bg-success-transparent">2525</span></button>
                    <button type="button" class="list-group-item list-group-item-action" disabled>A disabled item meant to be disabled<span class="badge float-end bg-danger-transparent">21</span></button>
                </div>`},I={script:`
   <ul class="list-group">
                <li :class="list-group-item {idx.class}" v-for="(idx,index) in listData.ContextualButtons" :key="index">A simple default list group item</li>
            </ul>`,data:`
        ContextualButtons = [
        { id: 1, text: "A simple default list group item", class: "", class1: "" },
        { id: 2, text: "A simple primary list group item", class: "list-group-item-primary", class1: "" },
        { id: 3, text: "A simple secondary list group item", class: "list-group-item-secondary", class1: "" },
        { id: 4, text: "A simple success list group item", class: "list-group-item-success", class1: "" },
        { id: 5, text: "A simple danger list group item", class: "list-group-item-danger", class1: "" },
        { id: 6, text: "A simple warning list group item", class: "list-group-item-warning", class1: "" },
        { id: 7, text: "A simple info list group item", class: "list-group-item-info", class1: "" },
        { id: 8, text: "A simple light list group item", class: "list-group-item-light", class1: "" },
        { id: 9, text: "A simple dark list group item", class: "list-group-item-dark", class1: "text-white" }
    ]
        `},M={script:`
    <div class="list-group">
                <a href="javascript:void(0);" :class="list-group-item list-group-item-action list-group-item-{idx.class} " v-for="(idx,index) in listData.ContextualButtons" :key="index">{{idx.text}}</a>
            </div>`,data:`
       ContextualButtons = [
        { id: 1, text: "A simple default list group item", class: "", class1: "" },
        { id: 2, text: "A simple primary list group item", class: "list-group-item-primary", class1: "" },
        { id: 3, text: "A simple secondary list group item", class: "list-group-item-secondary", class1: "" },
        { id: 4, text: "A simple success list group item", class: "list-group-item-success", class1: "" },
        { id: 5, text: "A simple danger list group item", class: "list-group-item-danger", class1: "" },
        { id: 6, text: "A simple warning list group item", class: "list-group-item-warning", class1: "" },
        { id: 7, text: "A simple info list group item", class: "list-group-item-info", class1: "" },
        { id: 8, text: "A simple light list group item", class: "list-group-item-light", class1: "" },
        { id: 9, text: "A simple dark list group item", class: "list-group-item-dark", class1: "text-white" }
    ] `},S={script:`
      <ul class="list-group">
                <li :class="list-group-item list-item-solid-{idx.class} {idx.class1}" v-for="(idx,index) in listData.ContextualButtons" :key="index">{{idx.text}}</li>
            </ul>`,data:`
        ContextualButtons = [
        { id: 1, text: "A simple default list group item", class: "", class1: "" },
        { id: 2, text: "A simple primary list group item", class: "list-group-item-primary", class1: "" },
        { id: 3, text: "A simple secondary list group item", class: "list-group-item-secondary", class1: "" },
        { id: 4, text: "A simple success list group item", class: "list-group-item-success", class1: "" },
        { id: 5, text: "A simple danger list group item", class: "list-group-item-danger", class1: "" },
        { id: 6, text: "A simple warning list group item", class: "list-group-item-warning", class1: "" },
        { id: 7, text: "A simple info list group item", class: "list-group-item-info", class1: "" },
        { id: 8, text: "A simple light list group item", class: "list-group-item-light", class1: "" },
        { id: 9, text: "A simple dark list group item", class: "list-group-item-dark", class1: "text-white" }
    ] 
        `},V={script:`
     <div class="list-group">
                <a href="javascript:void(0);" :class="list-group-item list-group-item-action {idx.class1}" aria-current="true"  v-for="(idx,index) in listData.CustomButtons" :key="index">
                    <div class="d-sm-flex w-100 justify-content-between">
                        <h6 :class="mb-1 fw-medium {idx.color}">{{idx.heading}}</h6>
                        <small :class="idx.class2">{{idx.text1}}</small>
                    </div>
                    <p class="mb-1">{{idx.text2}}</p>
                    <small>{{idx.text3}}.</small>
                </a>
            </div>`,data:`
        CustomButtons = [
        { id: 1, heading: "Web page editors now use Lorem Ipsum?", text1: "3 days ago", class1: "active", text2: "There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour.", text3: "24,Nov 2022", class2: "", color: "text-fixed-white" },
        { id: 2, heading: "Richard McClintock, a Latin professor?", text1: "4 hrs ago", class1: "", text2: "Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature.", text3: "30,Nov 2022", class2: "text-muted", color: "" },
        { id: 3, heading: "It uses a dictionary of over 200 Latin words?", text1: "15 hrs ago", class1: "", text2: "Lorem Ipsum has been the industry's standard dummy text ever since the 1500s.", text3: "4,Nov 2022", class2: "text-muted", color: "" },
        { id: 4, heading: "The standard Lorem Ipsum used since the 1500s?", text1: "45 mins ago", class1: "", text2: "All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet.", text3: "28,Oct 2022", class2: "text-muted", color: "" },
    ]
        `},N={script:`
     <ol class="list-group list-group-numbered">
                <li class="list-group-item d-sm-flex justify-content-between align-items-start" v-for="(idx, index) in listData.SubheadingsButtons" :key="index">
                    <div class="ms-2 me-auto text-muted">
                        <div class="fw-medium fs-14 text-default">{{idx.text1}}</div>
                        {{idx.text2}}
                    </div>
                    <span :class="badge bg-{idx.class}">{{idx.text3}}</span>
                </li>
            </ol>`,data:`
        SubheadingsButtons = [
        { id: 1, text1: "What Happened?", text2: "Many experts have recently suggested may exist.", text3: "32 Views", class: "primary-transparent" },
        { id: 2, text1: "It Was Amazing!", text2: " His idea involved taking red.", text3: "52 Views", class: "secondary-transparent" },
        { id: 3, text1: "News Is A Great Weapon.", text2: "News can influence in many ways.", text3: "1,204 Views", class: "success-transparent" },
        { id: 4, text1: "majority have suffered.", text2: " If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything.", text3: "14 Views", class: "danger-transparent" }
    ]
        `},T={script:`
    <ol class="list-group list-group-numbered">
                    <li class="list-group-item">Simply dummy text of the printing.</li>
                    <li class="list-group-item">There are many variations of passages.</li>
                    <li class="list-group-item">All the Lorem Ipsum generators.</li>
                    <li class="list-group-item">Written in 45 BC. This book is a treatise on the theory.</li>
                    <li class="list-group-item">Randomised words which don't look.</li>
                    <li class="list-group-item">Always free from repetition, injected humour.</li>
                </ol>`},Z={script:`
     <ul class="list-group">
                    <li class="list-group-item">
                        <input class="form-check-input me-1 fw-medium" type="checkbox" value="" aria-label="..." checked>
                        Accurate information at any given point.
                    </li>
                    <li class="list-group-item">
                        <input class="form-check-input me-1 fw-medium" type="checkbox" value="" aria-label="...">
                        Hearing the information and responding.
                    </li>
                    <li class="list-group-item">
                        <input class="form-check-input me-1 fw-medium" type="checkbox" value="" aria-label="..." checked>
                        Setting up and customizing your own sales.
                    </li>
                    <li class="list-group-item">
                        <input class="form-check-input me-1 fw-medium" type="checkbox" value="" aria-label="..." checked>
                        New Admin Launched.
                    </li>
                    <li class="list-group-item">
                        <input class="form-check-input me-1 fw-medium" type="checkbox" value="" aria-label="...">
                        To maximize profits and improve productivity.
                    </li>
                    <li class="list-group-item">
                        <input class="form-check-input me-1 fw-medium" type="checkbox" value="" aria-label="...">
                        To have a complete 360° overview of sales information, having.
                    </li>
                </ul>`},j={script:`
    <div class="list-group">
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="radio" value="" name="list-radio" checked>
                        Accurate information at any given point.
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="radio" value="" name="list-radio" checked>
                        Hearing the information and responding.
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="radio" value="" name="list-radio" checked>
                        Setting up and customizing your own sales.
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="radio" value="" name="list-radio">
                        New Admin Launched.
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="radio" value="" name="list-radio">
                        To maximize profits and improve productivity.
                    </label>
                    <label class="list-group-item">
                        <input class="form-check-input me-1" type="radio" value="" name="list-radio">
                        To have a complete 360° overview of sales information, having.
                    </label>
                </div>`},W={script:`
     <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center fw-medium" v-for="(idx, index) in listData.ListbadgesButtons" :key="index">
                    {{idx.text1}}
                    <span :class="badge bg-{idx.class} {idx.color}">{{idx.text2}}</span>
                </li>
            </ul>`,data:`
        ListbadgesButtons = [
        { id: 1, text1: "Groceries", text2: "Available", class: "primary", color: "" },
        { id: 2, text1: "Furniture", text2: "Buy", class: "secondary", color: "" },
        { id: 3, text1: "Beauty", text2: "32", class: "danger", color: "rounded-pill" },
        { id: 4, text1: "Books", text2: "New", class: "light", color: "text-default" },
        { id: 5, text1: "Toys", text2: "Hot", class: "info-gradient", color: "" },
        { id: 7, text1: "Mobiles", text2: "Sold Out", class: "warning", color: "" },
    ]
        `},z={script:`
    <ul :class="mb-3 list-group list-group-horizontal{idx.class}" v-for="(idx, index) in listData.HorizontalButtons" :key="index">
                <li class="list-group-item">An item</li>
                <li class="list-group-item">A second item</li>
                <li class="list-group-item">A third item</li>
            </ul>`,data:`
         HorizontalButtons = [
        { id: 1, class: "" },
        { id: 2, class: "-sm" },
        { id: 3, class: "-md" },
        { id: 4, class: "-lg" },
        { id: 5, class: "-xl" },
        { id: 6, class: "-xxl" },
    ]
        `};const D=[{id:1,src:h,text:"Alicia Sierra"},{id:2,src:f,text:"Samantha Mery"},{id:3,src:v,text:"Juliana Pena"},{id:4,src:b,text:"Adam Smith"},{id:5,src:y,text:"Farhaan Amhed"}],$=[{id:1,class:"bi bi-house-door",text:"Home",class1:"active",class2:"white"},{id:2,class:"bi bi-bell",text:"Notifications",class1:"",class2:"primary"},{id:3,class:"bi bi-gift",text:"Sent Messages",class1:"",class2:"primary"},{id:4,class:"bi bi-person",text:"New Requests",class1:"",class2:"primary"},{id:5,class:"bi bi-trash3",text:"Deleted Messages",class1:"",class2:"primary"}],R=[{id:1,class:"white",text:"California",class1:"active",text1:"C",color:"default"},{id:2,class:"secondary",text:"New Jersey",class1:"",text1:"N",color:""},{id:3,class:"info",text:"Los Angeles",class1:"",text1:"L",color:""},{id:4,class:"warning",text:"Miami Florida",class1:"",text1:"M",color:""},{id:5,class:"success",text:"Washington D.C",class1:"disabled",text1:"W",color:""}],g=[{id:1,text:"A simple default list group item",class:"light",class1:""},{id:2,text:"A simple primary list group item",class:"primary",class1:""},{id:3,text:"A simple secondary list group item",class:"secondary",class1:""},{id:4,text:"A simple success list group item",class:"success",class1:""},{id:5,text:"A simple danger list group item",class:"danger",class1:""},{id:6,text:"A simple warning list group item",class:"warning",class1:""},{id:7,text:"A simple info list group item",class:"info",class1:""},{id:8,text:"A simple light list group item",class:"light",class1:""},{id:9,text:"A simple dark list group item",class:"dark",class1:"text-white"}],F=[{id:1,heading:"Web page editors now use Lorem Ipsum?",text1:"3 days ago",class1:"active",text2:"There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour.",text3:"24,Nov 2022",class2:"",color:"text-fixed-white"},{id:2,heading:"Richard McClintock, a Latin professor?",text1:"4 hrs ago",class1:"",text2:"Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature.",text3:"30,Nov 2022",class2:"text-muted",color:""},{id:3,heading:"It uses a dictionary of over 200 Latin words?",text1:"15 hrs ago",class1:"",text2:"Lorem Ipsum has been the industry`s standard dummy text ever since the 1500s.",text3:"4,Nov 2022",class2:"text-muted",color:""},{id:4,heading:"The standard Lorem Ipsum used since the 1500s?",text1:"45 mins ago",class1:"",text2:"All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet.",text3:"28,Oct 2022",class2:"text-muted",color:""}],G=[{id:1,text1:"What Happened?",text2:"Many experts have recently suggested may exist.",text3:"32 Views",class:"primary-transparent"},{id:2,text1:"It Was Amazing!",text2:" His idea involved taking red.",text3:"52 Views",class:"secondary-transparent"},{id:3,text1:"News Is A Great Weapon.",text2:"News can influence in many ways.",text3:"1,204 Views",class:"success-transparent"},{id:4,text1:"majority have suffered.",text2:" If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything.",text3:"14 Views",class:"danger-transparent"}],J=[{id:1,text1:"Groceries",text2:"Available",class:"primary",color:""},{id:2,text1:"Furniture",text2:"Buy",class:"secondary",color:""},{id:3,text1:"Beauty",text2:"32",class:"danger",color:"rounded-pill"},{id:4,text1:"Books",text2:"New",class:"light",color:"text-default"},{id:5,text1:"Toys",text2:"Hot",class:"info-gradient",color:""},{id:7,text1:"Mobiles",text2:"Sold Out",class:"warning",color:""}],K=[{id:1,class:""},{id:2,class:"-sm"},{id:3,class:"-md"},{id:4,class:"-lg"},{id:5,class:"-xl"},{id:6,class:"-xxl"}],O={class:"row"},P={class:"col-xl-6"},q={class:"list-group"},E={class:"d-flex align-items-center"},U={class:"avatar avatar-sm"},Q=["src"],X={class:"ms-2 fw-medium"},Y={class:"col-xl-6"},tt={class:"list-group"},st={class:"d-flex align-items-center"},et={class:"fs-15"},it={class:"ms-2"},at={class:"col-xl-6"},lt={class:"col-xl-6"},ot={class:"col-xl-6"},rt={class:"list-group"},ct={class:"d-flex align-items-center"},nt={class:"ms-2"},dt={class:"col-xl-6"},mt={class:"row"},ut={class:"col-xl-6"},pt={class:"list-group"},gt={class:"col-xl-6"},xt={class:"list-group"},ht={class:"row"},ft={class:"col-xl-6"},vt={class:"list-group"},bt={class:"col-xl-6"},yt={class:"list-group"},wt={class:"d-sm-flex w-100 justify-content-between"},kt={class:"mb-1"},_t={class:"row"},At={class:"col-xl-6"},Lt={class:"list-group list-group-numbered"},Bt={class:"ms-2 me-auto text-muted"},Ht={class:"fw-medium fs-14 text-default"},Ct={class:"col-xl-6"},It={class:"col-xl-6"},Mt={class:"col-xl-6"},St={class:"col-xl-6"},Vt={class:"list-group"},Nt={class:"col-xl-6"},Tt={__name:"list-group",setup(Zt){const x={title:"Ui Elements",currentpage:"List Group",activepage:"List Group"};return(jt,m)=>(a(),i(u,null,[o(w,{propData:x}),t("div",O,[t("div",P,[o(c,{title:"Basic List",code:_},{default:n(()=>[t("ul",q,[(a(!0),i(u,null,p(D,(s,l)=>(a(),i("li",{class:r("list-group-item "),key:l},[t("div",E,[t("span",U,[t("img",{src:s.src,alt:"img"},null,8,Q)]),t("div",X,d(s.text),1)])]))),128))])]),_:1},8,["code"])]),t("div",Y,[o(c,{title:"Active items",code:A},{default:n(()=>[t("ul",tt,[(a(!0),i(u,null,p($,(s,l)=>(a(),i("li",{class:r(`list-group-item ${s.class1} ${s.liclass}`),key:l},[t("div",st,[t("div",null,[t("span",et,[t("i",{class:r(s.class)},null,2)])]),t("div",it,d(s.text),1)])],2))),128))])]),_:1},8,["code"])]),t("div",at,[o(c,{title:"Disabled items",code:L},{default:n(()=>m[0]||(m[0]=[t("ul",{class:"list-group"},[t("li",{class:"list-group-item disabled","aria-disabled":"true"},"A disabled item meant to be disabled"),t("li",{class:"list-group-item"},"Simply dummy text of the printing"),t("li",{class:"list-group-item"},"There are many variations of passages"),t("li",{class:"list-group-item"},"All the Lorem Ipsum generators"),t("li",{class:"list-group-item"},"Written in 45 BC. This book is a treatise on the theory")],-1)])),_:1,__:[0]},8,["code"])]),t("div",lt,[o(c,{title:"Flush",code:B},{default:n(()=>m[1]||(m[1]=[t("ul",{class:"list-group list-group-flush"},[t("li",{class:"list-group-item fw-medium"},[t("i",{class:"bi bi-envelope align-middle me-2 text-muted"}),e("Asish Trivedhi"),t("span",{class:"ms-1 text-muted fw-normal d-inline-block"},"(+1023-84534)")]),t("li",{class:"list-group-item fw-medium"},[t("i",{class:"bi bi-tiktok align-middle me-2 text-muted"}),e("Alezander Russo"),t("span",{class:"ms-1 text-muted fw-normal d-inline-block"},"(+7546-12342)")]),t("li",{class:"list-group-item fw-medium"},[t("i",{class:"bi bi-whatsapp align-middle me-2 text-muted"}),e("Karem Smith"),t("span",{class:"ms-1 text-muted fw-normal d-inline-block"},"(+9944-56632)")]),t("li",{class:"list-group-item fw-medium"},[t("i",{class:"bi bi-facebook align-middle me-2 text-muted"}),e("Melissa Brien"),t("span",{class:"ms-1 text-muted fw-normal d-inline-block"},"(+1023-34323)")]),t("li",{class:"list-group-item fw-medium"},[t("i",{class:"bi bi-instagram align-middle me-2 text-muted"}),e("Kamala Harris"),t("span",{class:"ms-1 text-muted fw-normal d-inline-block"},"(+91-63421)")])],-1)])),_:1,__:[1]},8,["code"])]),t("div",ot,[o(c,{title:"Links",code:H},{default:n(()=>[t("div",rt,[(a(!0),i(u,null,p(R,(s,l)=>(a(),i("a",{href:"javascript:void(0);",class:r(`list-group-item list-group-item-action ${s.class1}`),"aria-current":"true",key:l},[t("div",ct,[t("div",null,[t("span",{class:r(`avatar avatar-xs bg-${s.class} text-${s.color} avatar-rounded`)},d(s.text1),3)]),t("div",nt,d(s.text),1)])],2))),128))])]),_:1},8,["code"])]),t("div",dt,[o(c,{title:"buttons",code:C},{default:n(()=>m[2]||(m[2]=[t("div",{class:"list-group"},[t("button",{type:"button",class:"list-group-item list-group-item-action active","aria-current":"true"},[e("Simply dummy text of the printing"),t("span",{class:"badge float-end bg-primary"},"243")]),t("button",{type:"button",class:"list-group-item list-group-item-action"},[e("There are many variations of passages"),t("span",{class:"badge float-end bg-secondary-transparent"},"35")]),t("button",{type:"button",class:"list-group-item list-group-item-action"},[e("All the Lorem Ipsum generators"),t("span",{class:"badge float-end bg-info-transparent"},"132")]),t("button",{type:"button",class:"list-group-item list-group-item-action"},[e("All the Lorem Ipsum generators"),t("span",{class:"badge float-end bg-success-transparent"},"2525")]),t("button",{type:"button",class:"list-group-item list-group-item-action",disabled:""},[e("A disabled item meant to be disabled"),t("span",{class:"badge float-end bg-danger-transparent"},"21")])],-1)])),_:1,__:[2]},8,["code"])])]),t("div",mt,[t("div",ut,[o(c,{title:"Contextual classes",code:I},{default:n(()=>[t("ul",pt,[(a(!0),i(u,null,p(g,(s,l)=>(a(),i("li",{class:r(`list-group-item list-group-item-${s.class} `),key:l},d(s.text),3))),128))])]),_:1},8,["code"])]),t("div",gt,[o(c,{title:"Contextual classes with hover styles",code:M},{default:n(()=>[t("div",xt,[(a(!0),i(u,null,p(g,(s,l)=>(a(),i("a",{href:"javascript:void(0);",class:r(`list-group-item list-group-item-action list-group-item-${s.class} `),key:l},d(s.text),3))),128))])]),_:1},8,["code"])])]),t("div",ht,[t("div",ft,[o(c,{title:"Solid Colored Lists",code:S},{default:n(()=>[t("ul",vt,[(a(!0),i(u,null,p(g,(s,l)=>(a(),i("li",{class:r(`list-group-item list-item-solid-${s.class} ${s.class1}`),key:l},d(s.text),3))),128))])]),_:1},8,["code"])]),t("div",bt,[o(c,{title:"Custom content",code:V},{default:n(()=>[t("div",yt,[(a(!0),i(u,null,p(F,(s,l)=>(a(),i("a",{href:"javascript:void(0);",class:r(`list-group-item list-group-item-action ${s.class1}`),"aria-current":"true",key:l},[t("div",wt,[t("h6",{class:r(`mb-1 fw-medium ${s.color}`)},d(s.heading),3),t("small",{class:r(s.class2)},d(s.text1),3)]),t("p",kt,d(s.text2),1),t("small",{class:r(`${s.id===1?"":"text-muted"}`)},d(s.text3)+".",3)],2))),128))])]),_:1},8,["code"])])]),t("div",_t,[t("div",At,[o(c,{title:"Sub headings",code:N},{default:n(()=>[t("ol",Lt,[(a(!0),i(u,null,p(G,(s,l)=>(a(),i("li",{class:r("list-group-item d-sm-flex justify-content-between align-items-start"),key:l},[t("div",Bt,[t("div",Ht,d(s.text1),1),e(" "+d(s.text2),1)]),t("span",{class:r(`badge bg-${s.class}`)},d(s.text3),3)]))),128))])]),_:1},8,["code"])]),t("div",Ct,[o(c,{title:"Numbered Lists",code:T},{default:n(()=>m[3]||(m[3]=[t("ol",{class:"list-group list-group-numbered"},[t("li",{class:"list-group-item"},"Simply dummy text of the printing."),t("li",{class:"list-group-item"},"There are many variations of passages."),t("li",{class:"list-group-item"},"All the Lorem Ipsum generators."),t("li",{class:"list-group-item"},"Written in 45 BC. This book is a treatise on the theory. "),t("li",{class:"list-group-item"},"Randomised words which don't look."),t("li",{class:"list-group-item"},"Always free from repetition, injected humour.")],-1)])),_:1,__:[3]},8,["code"])]),t("div",It,[o(c,{title:"List With Checkboxes",code:Z},{default:n(()=>m[4]||(m[4]=[t("ul",{class:"list-group"},[t("li",{class:"list-group-item"},[t("input",{class:"form-check-input me-1 fw-medium",type:"checkbox",value:"","aria-label":"...",checked:""}),e(" Accurate information at any given point. ")]),t("li",{class:"list-group-item"},[t("input",{class:"form-check-input me-1 fw-medium",type:"checkbox",value:"","aria-label":"..."}),e(" Hearing the information and responding. ")]),t("li",{class:"list-group-item"},[t("input",{class:"form-check-input me-1 fw-medium",type:"checkbox",value:"","aria-label":"...",checked:""}),e(" Setting up and customizing your own sales. ")]),t("li",{class:"list-group-item"},[t("input",{class:"form-check-input me-1 fw-medium",type:"checkbox",value:"","aria-label":"...",checked:""}),e(" New Admin Launched. ")]),t("li",{class:"list-group-item"},[t("input",{class:"form-check-input me-1 fw-medium",type:"checkbox",value:"","aria-label":"..."}),e(" To maximize profits and improve productivity. ")]),t("li",{class:"list-group-item"},[t("input",{class:"form-check-input me-1 fw-medium",type:"checkbox",value:"","aria-label":"..."}),e(" To have a complete 360° overview of sales information, having. ")])],-1)])),_:1,__:[4]},8,["code"])]),t("div",Mt,[o(c,{title:"List With Radios",code:j},{default:n(()=>m[5]||(m[5]=[t("div",{class:"list-group"},[t("label",{class:"list-group-item"},[t("input",{class:"form-check-input me-1",type:"radio",value:"",name:"list-radio",checked:""}),e(" Accurate information at any given point. ")]),t("label",{class:"list-group-item"},[t("input",{class:"form-check-input me-1",type:"radio",value:"",name:"list-radio",checked:""}),e(" Hearing the information and responding. ")]),t("label",{class:"list-group-item"},[t("input",{class:"form-check-input me-1",type:"radio",value:"",name:"list-radio",checked:""}),e(" Setting up and customizing your own sales. ")]),t("label",{class:"list-group-item"},[t("input",{class:"form-check-input me-1",type:"radio",value:"",name:"list-radio"}),e(" New Admin Launched. ")]),t("label",{class:"list-group-item"},[t("input",{class:"form-check-input me-1",type:"radio",value:"",name:"list-radio"}),e(" To maximize profits and improve productivity. ")]),t("label",{class:"list-group-item"},[t("input",{class:"form-check-input me-1",type:"radio",value:"",name:"list-radio"}),e(" To have a complete 360° overview of sales information, having. ")])],-1)])),_:1,__:[5]},8,["code"])]),t("div",St,[o(c,{title:"List With badges",code:W},{default:n(()=>[t("ul",Vt,[(a(!0),i(u,null,p(J,(s,l)=>(a(),i("li",{class:r("list-group-item d-flex justify-content-between align-items-center fw-medium"),key:l},[e(d(s.text1)+" ",1),t("span",{class:r(`badge bg-${s.class} ${s.color}`)},d(s.text2),3)]))),128))])]),_:1},8,["code"])]),t("div",Nt,[o(c,{title:"Horizontal",code:z},{default:n(()=>[(a(!0),i(u,null,p(K,(s,l)=>(a(),i("ul",{class:r(`mb-3 list-group list-group-horizontal${s.class}`),key:l},m[6]||(m[6]=[t("li",{class:"list-group-item"},"An item",-1),t("li",{class:"list-group-item"},"A second item",-1),t("li",{class:"list-group-item"},"A third item",-1)]),2))),128))]),_:1},8,["code"])])])],64))}},Kt=k(Tt,[["__scopeId","data-v-6bf25ff3"]]);export{Kt as default};
