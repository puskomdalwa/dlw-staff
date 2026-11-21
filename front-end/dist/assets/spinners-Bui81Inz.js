import{_ as b}from"./pageheader-D3DwWmpK.js";import{S as l}from"./showcodeCard-D4bM6EJy.js";import{_ as m,c as e,e as d,f as n,g as s,w as a,F as t,s as c,A as p,j as u}from"./index-BTZJWR2b.js";let h={script:`
    <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>`},y={script:`
    <div :class="spinner-border text-{idx.color}" role="status" v-for="(idx, index) in spinnerData.Colorspinner" :key="index">
                <span class="visually-hidden">Loading...</span>
            </div>`,data:`
         Colorspinner = [
	{ id: 1, color: "primary" },
	{ id: 5, color: "secondary" },
	{ id: 6, color: "success" },
	{ id: 7, color: "danger" },
	{ id: 8, color: "warning" },
	{ id: 9, color: "info" },
	{ id: 10, color: "light" },
	{ id: 11, color: "dark" }
]`},x={script:`
    <div class="spinner-grow" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>`},_={script:`
     <div :class="spinner-grow text-{idx.color}" role="status" v-for="(idx, index) in spinnerData.Colorspinner" :key="index">
                <span class="visually-hidden">Loading...</span>
            </div>`,data:`
            Colorspinner = [
       { id: 1, color: "primary" },
       { id: 5, color: "secondary" },
       { id: 6, color: "success" },
       { id: 7, color: "danger" },
       { id: 8, color: "warning" },
       { id: 9, color: "info" },
       { id: 10, color: "light" },
       { id: 11, color: "dark" }
   ]`},f={script:`
    <div class="d-flex justify-content-center mb-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <strong>Loading...</strong>
                    <div class="spinner-border ms-auto" role="status" aria-hidden="true"></div>
                </div>`},w={script:`
    <div class="clearfix mb-4">
                    <div class="spinner-border float-end" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="clearfix">
                    <div class="spinner-border float-start" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>`},L={script:`
    <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`},k={script:`
   <div class="spinner-border spinner-border-sm me-4" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow spinner-grow-sm me-4" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-border me-4" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="spinner-grow" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>`},C={script:`
   <div class="spinner-border m-5" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>`},S={script:`
     <div class="btn-list">
                <button class="btn btn-primary-light" type="button" disabled>
                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    <span class="visually-hidden">Loading...</span>
                </button>
                <button class="btn btn-primary-light" type="button" disabled>
                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    Loading...
                </button>
                <button class="btn btn-primary-light" type="button" disabled>
                    <span class="spinner-grow spinner-grow-sm align-middle" role="status" aria-hidden="true"></span>
                    <span class="visually-hidden">Loading...</span>
                </button>
                <button :class="btn btn-{idx.color}" type="button" disabled v-for="(idx, index) in spinnerData.Buttonspinner" :key="index">
                        <span class="spinner-grow spinner-grow-sm align-middle" role="status" aria-hidden="true"></span>
                        Loading...
                    </button>
            </div>`,data:`
        Buttonspinner = [
	{ id: 1, color: "primary-light", class: "" },
	{ id: 2, color: "secondary-light", class: "" },
	{ id: 3, color: "success-light", class: "" },
	{ id: 4, color: "info-light", class: "" },
	{ id: 5, color: "warning-light", class: "" },
	{ id: 6, color: "danger-light", class: "" },
	{ id: 7, color: "light", class: "" },
	{ id: 8, color: "dark", class: "text-dark" }
]
        `},g=[{id:1,color:"primary"},{id:5,color:"secondary"},{id:6,color:"success"},{id:7,color:"danger"},{id:8,color:"warning"},{id:9,color:"info"},{id:10,color:"light"},{id:11,color:"dark"}],B=[{id:1,color:"primary-light",class:""},{id:2,color:"secondary-light",class:""},{id:3,color:"success-light",class:""},{id:4,color:"info-light",class:""},{id:5,color:"warning-light",class:""},{id:6,color:"danger-light",class:""},{id:7,color:"light",class:""},{id:8,color:"dark",class:"text-dark"}];const A={class:"row"},F={class:"col-xl-6"},D={class:"col-xl-6"},G={class:"row"},T={class:"col-xl-12"},$={class:"col-xl-12"},j={class:"row"},z={class:"col-xl-12"},N={class:"col-xl-12"},V={class:"row"},E={class:"col-xl-6"},M={class:"col-xl-6"},I={class:"row"},P={class:"col-xl-12"},U={class:"btn-list"},q={__name:"spinners",setup(H){const v={title:"Ui Elements",currentpage:"Spinners",activepage:"Spinners"};return(J,i)=>(d(),e(t,null,[n(b,{propData:v}),s("div",A,[s("div",F,[n(l,{title:"Border spinner",code:h},{default:a(()=>i[0]||(i[0]=[s("div",{class:"spinner-border",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1)])),_:1,__:[0]},8,["code"])]),s("div",D,[n(l,{title:"Growing spinner",code:x},{default:a(()=>i[1]||(i[1]=[s("div",{class:"spinner-grow",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1)])),_:1,__:[1]},8,["code"])])]),s("div",G,[s("div",T,[n(l,{title:"Colors",code:y},{default:a(()=>[(d(!0),e(t,null,c(g,(r,o)=>(d(),e("div",{class:p(`spinner-border me-1 text-${r.color}`),role:"status",key:o},i[2]||(i[2]=[s("span",{class:"visually-hidden"},"Loading...",-1)]),2))),128))]),_:1},8,["code"])]),s("div",$,[n(l,{title:"Growing Color spinners",code:_},{default:a(()=>[(d(!0),e(t,null,c(g,(r,o)=>(d(),e("div",{class:p(`spinner-grow me-1 text-${r.color}`),role:"status",key:o},i[3]||(i[3]=[s("span",{class:"visually-hidden"},"Loading...",-1)]),2))),128))]),_:1},8,["code"])])]),s("div",j,[s("div",z,[n(l,{title:"Alignment Flex",code:f},{default:a(()=>i[4]||(i[4]=[s("div",{class:"d-flex justify-content-center mb-4"},[s("div",{class:"spinner-border",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")])],-1),s("div",{class:"d-flex align-items-center"},[s("strong",null,"Loading..."),s("div",{class:"spinner-border ms-auto",role:"status","aria-hidden":"true"})],-1)])),_:1,__:[4]},8,["code"])]),s("div",N,[n(l,{title:"Alignment Float",code:w},{default:a(()=>i[5]||(i[5]=[s("div",{class:"clearfix mb-4"},[s("div",{class:"spinner-border float-end",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")])],-1),s("div",{class:"clearfix"},[s("div",{class:"spinner-border float-start",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")])],-1)])),_:1,__:[5]},8,["code"])])]),s("div",V,[s("div",E,[n(l,{title:"Alignment Text center",code:L},{default:a(()=>i[6]||(i[6]=[s("div",{class:"text-center"},[s("div",{class:"spinner-border",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")])],-1)])),_:1,__:[6]},8,["code"]),n(l,{title:"Spinner Sizes","custom-card-body-class":`d-flex 
                align-items-center`,code:k},{default:a(()=>i[7]||(i[7]=[s("div",{class:"spinner-border spinner-border-sm me-4",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1),s("div",{class:"spinner-grow spinner-grow-sm me-4",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1),s("div",{class:"spinner-border me-4",style:{width:"3rem",height:"3rem"},role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1),s("div",{class:"spinner-grow",style:{width:"3rem",height:"3rem"},role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1)])),_:1,__:[7]},8,["code"])]),s("div",M,[n(l,{title:"Alignment Margin",code:C},{default:a(()=>i[8]||(i[8]=[s("div",{class:"spinner-border m-5",role:"status"},[s("span",{class:"visually-hidden"},"Loading...")],-1)])),_:1,__:[8]},8,["code"])])]),s("div",I,[s("div",P,[n(l,{title:"Buttons",code:S},{default:a(()=>[s("div",U,[i[10]||(i[10]=s("button",{class:"btn btn-primary-light",type:"button",disabled:""},[s("span",{class:"spinner-border spinner-border-sm align-middle",role:"status","aria-hidden":"true"}),s("span",{class:"visually-hidden"},"Loading...")],-1)),i[11]||(i[11]=s("button",{class:"btn btn-primary-light",type:"button",disabled:""},[s("span",{class:"spinner-border spinner-border-sm align-middle",role:"status","aria-hidden":"true"}),u(" Loading... ")],-1)),i[12]||(i[12]=s("button",{class:"btn btn-primary-light",type:"button",disabled:""},[s("span",{class:"spinner-grow spinner-grow-sm align-middle",role:"status","aria-hidden":"true"}),s("span",{class:"visually-hidden"},"Loading...")],-1)),(d(!0),e(t,null,c(B,(r,o)=>(d(),e("button",{class:p(`btn btn-${r.color}`),type:"button",disabled:"",key:o},i[9]||(i[9]=[s("span",{class:"spinner-grow spinner-grow-sm align-middle",role:"status","aria-hidden":"true"},null,-1),u(" Loading... ",-1)]),2))),128))])]),_:1},8,["code"])])])],64))}},R=m(q,[["__scopeId","data-v-a9d1864e"]]);export{R as default};
