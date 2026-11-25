import{r as $,P as Ne,z as A,ag as p,K as P,o as _t,ac as Me,A as me,a7 as Xt,X as Jt,S as ee,ah as Qt,l as er,aa as nt,a8 as tr,a1 as rt,d as rr,c as se,e as oe,f as Ce,g as o,w as Ie,p as fe,t as Se,i as _e,v as Qe,x as ir,Z as ar,F as lr,_ as nr}from"./index-DNX_QOTQ.js";import{_ as Ft}from"./pageheader-F8tiMIeW.js";import{S as xe}from"./showcodeCard-CDopq2Aa.js";function B(e){return typeof e=="function"}function xt(e){return e==null}const be=e=>e!==null&&!!e&&typeof e=="object"&&!Array.isArray(e);function st(e){return Number(e)>=0}function sr(e){const t=parseFloat(e);return isNaN(t)?e:t}function or(e){return typeof e=="object"&&e!==null}function dr(e){return e==null?e===void 0?"[object Undefined]":"[object Null]":Object.prototype.toString.call(e)}function bt(e){if(!or(e)||dr(e)!=="[object Object]")return!1;if(Object.getPrototypeOf(e)===null)return!0;let t=e;for(;Object.getPrototypeOf(t)!==null;)t=Object.getPrototypeOf(t);return Object.getPrototypeOf(e)===t}function Pe(e,t){return Object.keys(t).forEach(r=>{if(bt(t[r])&&bt(e[r])){e[r]||(e[r]={}),Pe(e[r],t[r]);return}e[r]=t[r]}),e}function Ae(e){const t=e.split(".");if(!t.length)return"";let r=String(t[0]);for(let i=1;i<t.length;i++){if(st(t[i])){r+=`[${t[i]}]`;continue}r+=`.${t[i]}`}return r}const ur={};function cr(e){return ur[e]}function pt(e,t,r){typeof r.value=="object"&&(r.value=F(r.value)),!r.enumerable||r.get||r.set||!r.configurable||!r.writable||t==="__proto__"?Object.defineProperty(e,t,r):e[t]=r.value}function F(e){if(typeof e!="object")return e;var t=0,r,i,n,s=Object.prototype.toString.call(e);if(s==="[object Object]"?n=Object.create(e.__proto__||null):s==="[object Array]"?n=Array(e.length):s==="[object Set]"?(n=new Set,e.forEach(function(m){n.add(F(m))})):s==="[object Map]"?(n=new Map,e.forEach(function(m,v){n.set(F(v),F(m))})):s==="[object Date]"?n=new Date(+e):s==="[object RegExp]"?n=new RegExp(e.source,e.flags):s==="[object DataView]"?n=new e.constructor(F(e.buffer)):s==="[object ArrayBuffer]"?n=e.slice(0):s.slice(-6)==="Array]"&&(n=new e.constructor(e)),n){for(i=Object.getOwnPropertySymbols(e);t<i.length;t++)pt(n,i[t],Object.getOwnPropertyDescriptor(e,i[t]));for(t=0,i=Object.getOwnPropertyNames(e);t<i.length;t++)Object.hasOwnProperty.call(n,r=i[t])&&n[r]===e[r]||pt(n,r,Object.getOwnPropertyDescriptor(e,r))}return n||e}const ot=Symbol("vee-validate-form"),vr=Symbol("vee-validate-form-context"),fr=Symbol("vee-validate-field-instance"),ht=Symbol("Default empty value"),mr=typeof window<"u";function it(e){return B(e)&&!!e.__locatorRef}function J(e){return!!e&&B(e.parse)&&e.__type==="VVTypedSchema"}function Te(e){return!!e&&B(e.validate)}function At(e){return e==="checkbox"||e==="radio"}function br(e){return be(e)||Array.isArray(e)}function pr(e){return Array.isArray(e)?e.length===0:be(e)&&Object.keys(e).length===0}function Re(e){return/^\[.+\]$/i.test(e)}function hr(e){return Et(e)&&e.multiple}function Et(e){return e.tagName==="SELECT"}function yr(e){return Pt(e)&&e.target&&"submit"in e.target}function Pt(e){return e?!!(typeof Event<"u"&&B(Event)&&e instanceof Event||e&&e.srcElement):!1}function z(e,t){if(e===t)return!0;if(e&&t&&typeof e=="object"&&typeof t=="object"){if(e.constructor!==t.constructor)return!1;var r,i,n;if(Array.isArray(e)){if(r=e.length,r!=t.length)return!1;for(i=r;i--!==0;)if(!z(e[i],t[i]))return!1;return!0}if(e instanceof Map&&t instanceof Map){if(e.size!==t.size)return!1;for(i of e.entries())if(!t.has(i[0]))return!1;for(i of e.entries())if(!z(i[1],t.get(i[0])))return!1;return!0}if(gt(e)&&gt(t))return!(e.size!==t.size||e.name!==t.name||e.lastModified!==t.lastModified||e.type!==t.type);if(e instanceof Set&&t instanceof Set){if(e.size!==t.size)return!1;for(i of e.entries())if(!t.has(i[0]))return!1;return!0}if(ArrayBuffer.isView(e)&&ArrayBuffer.isView(t)){if(r=e.length,r!=t.length)return!1;for(i=r;i--!==0;)if(e[i]!==t[i])return!1;return!0}if(e.constructor===RegExp)return e.source===t.source&&e.flags===t.flags;if(e.valueOf!==Object.prototype.valueOf)return e.valueOf()===t.valueOf();if(e.toString!==Object.prototype.toString)return e.toString()===t.toString();if(e=yt(e),t=yt(t),n=Object.keys(e),r=n.length,r!==Object.keys(t).length)return!1;for(i=r;i--!==0;)if(!Object.prototype.hasOwnProperty.call(t,n[i]))return!1;for(i=r;i--!==0;){var s=n[i];if(!z(e[s],t[s]))return!1}return!0}return e!==e&&t!==t}function yt(e){return Object.fromEntries(Object.entries(e).filter(([,t])=>t!==void 0))}function gt(e){return mr?e instanceof File:!1}function dt(e){return Re(e)?e.replace(/\[|\]/gi,""):e}function L(e,t,r){return e?Re(t)?e[dt(t)]:(t||"").split(/\.|\[(\d+)\]/).filter(Boolean).reduce((n,s)=>br(n)&&s in n?n[s]:r,e):r}function ie(e,t,r){if(Re(t)){e[dt(t)]=r;return}const i=t.split(/\.|\[(\d+)\]/).filter(Boolean);let n=e;for(let s=0;s<i.length;s++){if(s===i.length-1){n[i[s]]=r;return}(!(i[s]in n)||xt(n[i[s]]))&&(n[i[s]]=st(i[s+1])?[]:{}),n=n[i[s]]}}function et(e,t){if(Array.isArray(e)&&st(t)){e.splice(Number(t),1);return}be(e)&&delete e[t]}function kt(e,t){if(Re(t)){delete e[dt(t)];return}const r=t.split(/\.|\[(\d+)\]/).filter(Boolean);let i=e;for(let s=0;s<r.length;s++){if(s===r.length-1){et(i,r[s]);break}if(!(r[s]in i)||xt(i[r[s]]))break;i=i[r[s]]}const n=r.map((s,m)=>L(e,r.slice(0,m).join(".")));for(let s=n.length-1;s>=0;s--)if(pr(n[s])){if(s===0){et(e,r[0]);continue}et(n[s-1],r[s-1])}}function G(e){return Object.keys(e)}function jt(e,t=void 0){const r=nt();return r?.provides[e]||tr(e,t)}function gr(e,t,r){if(Array.isArray(e)){const i=[...e],n=i.findIndex(s=>z(s,t));return n>=0?i.splice(n,1):i.push(t),i}return z(e,t)?r:t}function St(e,t=0){let r=null,i=[];return function(...n){return r&&clearTimeout(r),r=setTimeout(()=>{const s=e(...n);i.forEach(m=>m(s)),i=[]},t),new Promise(s=>i.push(s))}}function kr(e,t){return be(t)&&t.number?sr(e):e}function at(e,t){let r;return async function(...n){const s=e(...n);r=s;const m=await s;return s!==r?m:(r=void 0,t(m,n))}}function lt(e){return Array.isArray(e)?e:e?[e]:[]}function Ue(e,t){const r={};for(const i in e)t.includes(i)||(r[i]=e[i]);return r}function Sr(e){let t=null,r=[];return function(...i){const n=ee(()=>{if(t!==n)return;const s=e(...i);r.forEach(m=>m(s)),r=[],t=null});return t=n,new Promise(s=>r.push(s))}}function tt(e){if(wt(e))return e._value}function wt(e){return"_value"in e}function Or(e){return e.type==="number"||e.type==="range"?Number.isNaN(e.valueAsNumber)?e.value:e.valueAsNumber:e.value}function ze(e){if(!Pt(e))return e;const t=e.target;if(At(t.type)&&wt(t))return tt(t);if(t.type==="file"&&t.files){const r=Array.from(t.files);return t.multiple?r:r[0]}if(hr(t))return Array.from(t.options).filter(r=>r.selected&&!r.disabled).map(tt);if(Et(t)){const r=Array.from(t.options).find(i=>i.selected);return r?tt(r):t.value}return Or(t)}function qt(e){const t={};return Object.defineProperty(t,"_$$isNormalized",{value:!0,writable:!1,enumerable:!1,configurable:!1}),e?be(e)&&e._$$isNormalized?e:be(e)?Object.keys(e).reduce((r,i)=>{const n=Vr(e[i]);return e[i]!==!1&&(r[i]=Ot(n)),r},t):typeof e!="string"?t:e.split("|").reduce((r,i)=>{const n=Cr(i);return n.name&&(r[n.name]=Ot(n.params)),r},t):t}function Vr(e){return e===!0?[]:Array.isArray(e)||be(e)?e:[e]}function Ot(e){const t=r=>typeof r=="string"&&r[0]==="@"?_r(r.slice(1)):r;return Array.isArray(e)?e.map(t):e instanceof RegExp?[e]:Object.keys(e).reduce((r,i)=>(r[i]=t(e[i]),r),{})}const Cr=e=>{let t=[];const r=e.split(":")[0];return e.includes(":")&&(t=e.split(":").slice(1).join(":").split(",")),{name:r,params:t}};function _r(e){const t=r=>{var i;return(i=L(r,e))!==null&&i!==void 0?i:r[e]};return t.__locatorRef=e,t}function Fr(e){return Array.isArray(e)?e.filter(it):G(e).filter(t=>it(e[t])).map(t=>e[t])}const xr={generateMessage:({field:e})=>`${e} is not valid.`,bails:!0,validateOnBlur:!0,validateOnChange:!0,validateOnInput:!1,validateOnModelUpdate:!0};let Ar=Object.assign({},xr);const Ee=()=>Ar;async function It(e,t,r={}){const i=r?.bails,n={name:r?.name||"{field}",rules:t,label:r?.label,bails:i??!0,formData:r?.values||{}},s=await Er(n,e);return Object.assign(Object.assign({},s),{valid:!s.errors.length})}async function Er(e,t){const r=e.rules;if(J(r)||Te(r))return jr(t,Object.assign(Object.assign({},e),{rules:r}));if(B(r)||Array.isArray(r)){const v={field:e.label||e.name,name:e.name,label:e.label,form:e.formData,value:t},b=Array.isArray(r)?r:[r],c=b.length,g=[];for(let V=0;V<c;V++){const _=b[V],O=await _(t,v);if(!(typeof O!="string"&&!Array.isArray(O)&&O)){if(Array.isArray(O))g.push(...O);else{const W=typeof O=="string"?O:Dt(v);g.push(W)}if(e.bails)return{errors:g}}}return{errors:g}}const i=Object.assign(Object.assign({},e),{rules:qt(r)}),n=[],s=Object.keys(i.rules),m=s.length;for(let v=0;v<m;v++){const b=s[v],c=await wr(i,t,{name:b,params:i.rules[b]});if(c.error&&(n.push(c.error),e.bails))return{errors:n}}return{errors:n}}function Pr(e){return!!e&&e.name==="ValidationError"}function Ut(e){return{__type:"VVTypedSchema",async parse(r,i){var n;try{return{output:await e.validate(r,{abortEarly:!1,context:i?.formData||{}}),errors:[]}}catch(s){if(!Pr(s))throw s;if(!(!((n=s.inner)===null||n===void 0)&&n.length)&&s.errors.length)return{errors:[{path:s.path,errors:s.errors}]};const m=s.inner.reduce((v,b)=>{const c=b.path||"";return v[c]||(v[c]={errors:[],path:c}),v[c].errors.push(...b.errors),v},{});return{errors:Object.values(m)}}}}}async function jr(e,t){const i=await(J(t.rules)?t.rules:Ut(t.rules)).parse(e,{formData:t.formData}),n=[];for(const s of i.errors)s.errors.length&&n.push(...s.errors);return{value:i.value,errors:n}}async function wr(e,t,r){const i=cr(r.name);if(!i)throw new Error(`No such validator '${r.name}' exists.`);const n=qr(r.params,e.formData),s={field:e.label||e.name,name:e.name,label:e.label,value:t,form:e.formData,rule:Object.assign(Object.assign({},r),{params:n})},m=await i(t,n,s);return typeof m=="string"?{error:m}:{error:m?void 0:Dt(s)}}function Dt(e){const t=Ee().generateMessage;return t?t(e):"Field is invalid"}function qr(e,t){const r=i=>it(i)?i(t):i;return Array.isArray(e)?e.map(r):Object.keys(e).reduce((i,n)=>(i[n]=r(e[n]),i),{})}async function Ir(e,t){const i=await(J(e)?e:Ut(e)).parse(F(t),{formData:F(t)}),n={},s={};for(const m of i.errors){const v=m.errors,b=(m.path||"").replace(/\["(\d+)"\]/g,(c,g)=>`[${g}]`);n[b]={valid:!v.length,errors:v},v.length&&(s[b]=v[0])}return{valid:!i.errors.length,results:n,errors:s,values:i.value,source:"schema"}}async function Ur(e,t,r){const n=G(e).map(async c=>{var g,V,_;const O=(g=r?.names)===null||g===void 0?void 0:g[c],q=await It(L(t,c),e[c],{name:O?.name||c,label:O?.label,values:t,bails:(_=(V=r?.bailsMap)===null||V===void 0?void 0:V[c])!==null&&_!==void 0?_:!0});return Object.assign(Object.assign({},q),{path:c})});let s=!0;const m=await Promise.all(n),v={},b={};for(const c of m)v[c.path]={valid:c.valid,errors:c.errors},c.valid||(s=!1,b[c.path]=c.errors[0]);return{valid:s,results:v,errors:b,source:"schema"}}let Vt=0;function Dr(e,t){const{value:r,initialValue:i,setInitialValue:n}=Nr(e,t.modelValue,t.form);if(!t.form){let b=function(O){var q;"value"in O&&(r.value=O.value),"errors"in O&&g(O.errors),"touched"in O&&(_.touched=(q=O.touched)!==null&&q!==void 0?q:_.touched),"initialValue"in O&&n(O.initialValue)};const{errors:c,setErrors:g}=zr(),V=Vt>=Number.MAX_SAFE_INTEGER?0:++Vt,_=Tr(r,i,c,t.schema);return{id:V,path:e,value:r,initialValue:i,meta:_,flags:{pendingUnmount:{[V]:!1},pendingReset:!1},errors:c,setState:b}}const s=t.form.createPathState(e,{bails:t.bails,label:t.label,type:t.type,validate:t.validate,schema:t.schema}),m=A(()=>s.errors);function v(b){var c,g,V;"value"in b&&(r.value=b.value),"errors"in b&&((c=t.form)===null||c===void 0||c.setFieldError(P(e),b.errors)),"touched"in b&&((g=t.form)===null||g===void 0||g.setFieldTouched(P(e),(V=b.touched)!==null&&V!==void 0?V:!1)),"initialValue"in b&&n(b.initialValue)}return{id:Array.isArray(s.id)?s.id[s.id.length-1]:s.id,path:e,value:r,errors:m,meta:s,initialValue:i,flags:s.__flags,setState:v}}function Nr(e,t,r){const i=$(P(t));function n(){return r?L(r.initialValues.value,P(e),P(i)):P(i)}function s(c){if(!r){i.value=c;return}r.setFieldInitialValue(P(e),c,!0)}const m=A(n);if(!r)return{value:$(n()),initialValue:m,setInitialValue:s};const v=Mr(t,r,m,e);return r.stageInitialValue(P(e),v,!0),{value:A({get(){return L(r.values,P(e))},set(c){r.setFieldValue(P(e),c,!1)}}),initialValue:m,setInitialValue:s}}function Mr(e,t,r,i){return Me(e)?P(e):e!==void 0?e:L(t.values,P(i),P(r))}function Tr(e,t,r,i){const n=A(()=>{var m,v,b;return(b=(v=(m=p(i))===null||m===void 0?void 0:m.describe)===null||v===void 0?void 0:v.call(m).required)!==null&&b!==void 0?b:!1}),s=Ne({touched:!1,pending:!1,valid:!0,required:n,validated:!!P(r).length,initialValue:A(()=>P(t)),dirty:A(()=>!z(P(e),P(t)))});return me(r,m=>{s.valid=!m.length},{immediate:!0,flush:"sync"}),s}function zr(){const e=$([]);return{errors:e,setErrors:t=>{e.value=lt(t)}}}function Fe(e,t,r){return At(void 0)?Br(e,t):Nt(e,t)}function Nt(e,t,r){const{initialValue:i,validateOnMount:n,bails:s,type:m,checkedValue:v,label:b,validateOnValueUpdate:c,uncheckedValue:g,controlled:V,keepValueOnUnmount:_,syncVModel:O,form:q}=Rr(),W=V?jt(ot):void 0,C=q||W,Z=A(()=>Ae(p(e))),X=A(()=>{if(p(C?.schema))return;const S=P(t);return Te(S)||J(S)||B(S)||Array.isArray(S)?S:qt(S)}),Be=!B(X.value)&&J(p(t)),{id:pe,value:he,initialValue:de,meta:U,setState:ye,errors:je,flags:ue}=Dr(Z,{modelValue:i,form:C,bails:s,label:b,type:m,validate:X.value?le:void 0,schema:Be?t:void 0}),Y=A(()=>je.value[0]);O&&Lr({value:he,prop:O,handleChange:D,shouldValidate:()=>c&&!ue.pendingReset});const ge=(f,S=!1)=>{U.touched=!0,S&&ae()};async function we(f){var S,E;if(C?.validateSchema){const{results:x}=await C.validateSchema(f);return(S=x[p(Z)])!==null&&S!==void 0?S:{valid:!0,errors:[]}}return X.value?It(he.value,X.value,{name:p(Z),label:p(b),values:(E=C?.values)!==null&&E!==void 0?E:{},bails:s}):{valid:!0,errors:[]}}const ae=at(async()=>(U.pending=!0,U.validated=!0,we("validated-only")),f=>(ue.pendingUnmount[T.id]||(ye({errors:f.errors}),U.pending=!1,U.valid=f.valid),f)),te=at(async()=>we("silent"),f=>(U.valid=f.valid,f));function le(f){return f?.mode==="silent"?te():ae()}function D(f,S=!0){const E=ze(f);Oe(E,S)}_t(()=>{if(n)return ae();(!C||!C.validateSchema)&&te()});function Le(f){U.touched=f}function ne(f){var S;const E=f&&"value"in f?f.value:de.value;ye({value:F(E),initialValue:F(E),touched:(S=f?.touched)!==null&&S!==void 0?S:!1,errors:f?.errors||[]}),U.pending=!1,U.validated=!1,te()}const ce=nt();function Oe(f,S=!0){he.value=ce&&O?kr(f,ce.props.modelModifiers):f,(S?ae:te)()}function qe(f){ye({errors:Array.isArray(f)?f:[f]})}const ut=A({get(){return he.value},set(f){Oe(f,c)}}),T={id:pe,name:Z,label:b,value:ut,meta:U,errors:je,errorMessage:Y,type:m,checkedValue:v,uncheckedValue:g,bails:s,keepValueOnUnmount:_,resetField:ne,handleReset:()=>ne(),validate:le,handleChange:D,handleBlur:ge,setState:ye,setTouched:Le,setErrors:qe,setValue:Oe};if(rt(fr,T),Me(t)&&typeof P(t)!="function"&&me(t,(f,S)=>{z(f,S)||(U.validated?ae():te())},{deep:!0}),!C)return T;const Ge=A(()=>{const f=X.value;return!f||B(f)||Te(f)||J(f)||Array.isArray(f)?{}:Object.keys(f).reduce((S,E)=>{const x=Fr(f[E]).map(re=>re.__locatorRef).reduce((re,Q)=>{const K=L(C.values,Q)||C.values[Q];return K!==void 0&&(re[Q]=K),re},{});return Object.assign(S,x),S},{})});return me(Ge,(f,S)=>{if(!Object.keys(f).length)return;!z(f,S)&&(U.validated?ae():te())}),er(()=>{var f;const S=(f=p(T.keepValueOnUnmount))!==null&&f!==void 0?f:p(C.keepValuesOnUnmount),E=p(Z);if(S||!C||ue.pendingUnmount[T.id]){C?.removePathState(E,pe);return}ue.pendingUnmount[T.id]=!0;const x=C.getPathState(E);if(Array.isArray(x?.id)&&x?.multiple?x?.id.includes(T.id):x?.id===T.id){if(x?.multiple&&Array.isArray(x.value)){const Q=x.value.findIndex(K=>z(K,p(T.checkedValue)));if(Q>-1){const K=[...x.value];K.splice(Q,1),C.setFieldValue(E,K)}Array.isArray(x.id)&&x.id.splice(x.id.indexOf(T.id),1)}else C.unsetPathValue(p(Z));C.removePathState(E,pe)}}),T}function Rr(e){return Object.assign(Object.assign({},{initialValue:void 0,validateOnMount:!1,bails:!0,label:void 0,validateOnValueUpdate:!0,keepValueOnUnmount:void 0,syncVModel:!1,controlled:!0}),{initialValue:void 0})}function Br(e,t,r){const i=jt(ot),n=void 0,s=void 0;function m(v){const b=v.handleChange,c=A(()=>{const V=p(v.value),_=p(n);return Array.isArray(V)?V.findIndex(O=>z(O,_))>=0:z(_,V)});function g(V,_=!0){var O,q;if(c.value===((O=V?.target)===null||O===void 0?void 0:O.checked)){_&&v.validate();return}const W=p(e),C=i?.getPathState(W),Z=ze(V);let X=(q=p(n))!==null&&q!==void 0?q:Z;i&&C?.multiple&&C.type==="checkbox"&&(X=gr(L(i.values,W)||[],X,void 0)),b(X,_)}return Object.assign(Object.assign({},v),{checked:c,checkedValue:n,uncheckedValue:s,handleChange:g})}return m(Nt(e,t))}function Lr({prop:e,value:t,handleChange:r,shouldValidate:i}){const n=nt();if(!n||!e)return;const s=typeof e=="string"?e:"modelValue",m=`update:${s}`;s in n.props&&(me(t,v=>{z(v,Ct(n,s))||n.emit(m,v)}),me(()=>Ct(n,s),v=>{if(v===ht&&t.value===void 0)return;const b=v===ht?void 0:v;z(b,t.value)||r(b,i())}))}function Ct(e,t){if(e)return e.props[t]}let Gr=0;const De=["bails","fieldsCount","id","multiple","type","validate"];function Mt(e){const t=e?.initialValues||{},r=Object.assign({},p(t)),i=P(e?.validationSchema);return i&&J(i)&&B(i.cast)?F(i.cast(r)||{}):F(r)}function $r(e){var t;const r=Gr++,i=e?.name||"Form";let n=0;const s=$(!1),m=$(!1),v=$(0),b=[],c=Ne(Mt(e)),g=$([]),V=$({}),_=$({}),O=Sr(()=>{_.value=g.value.reduce((l,a)=>(l[Ae(p(a.path))]=a,l),{})});function q(l,a){const d=D(l);if(!d){typeof l=="string"&&(V.value[Ae(l)]=lt(a));return}if(typeof l=="string"){const u=Ae(l);V.value[u]&&delete V.value[u]}d.errors=lt(a),d.valid=!d.errors.length}function W(l){G(l).forEach(a=>{q(a,l[a])})}e?.initialErrors&&W(e.initialErrors);const C=A(()=>{const l=g.value.reduce((a,d)=>(d.errors.length&&(a[p(d.path)]=d.errors),a),{});return Object.assign(Object.assign({},V.value),l)}),Z=A(()=>G(C.value).reduce((l,a)=>{const d=C.value[a];return d?.length&&(l[a]=d[0]),l},{})),X=A(()=>g.value.reduce((l,a)=>(l[p(a.path)]={name:p(a.path)||"",label:a.label||""},l),{})),Be=A(()=>g.value.reduce((l,a)=>{var d;return l[p(a.path)]=(d=a.bails)!==null&&d!==void 0?d:!0,l},{})),pe=Object.assign({},e?.initialErrors||{}),he=(t=e?.keepValuesOnUnmount)!==null&&t!==void 0?t:!1,{initialValues:de,originalInitialValues:U,setInitialValues:ye}=Zr(g,c,e),je=Wr(g,c,U,Z),ue=A(()=>g.value.reduce((l,a)=>{const d=L(c,p(a.path));return ie(l,p(a.path),d),l},{})),Y=e?.validationSchema;function ge(l,a){var d,u;const y=A(()=>L(de.value,p(l))),k=_.value[p(l)],h=a?.type==="checkbox"||a?.type==="radio";if(k&&h){k.multiple=!0;const H=n++;return Array.isArray(k.id)?k.id.push(H):k.id=[k.id,H],k.fieldsCount++,k.__flags.pendingUnmount[H]=!1,k}const w=A(()=>L(c,p(l))),I=p(l),N=ne.findIndex(H=>H===I);N!==-1&&ne.splice(N,1);const j=A(()=>{var H,Ve,Ke,He;const Xe=p(Y);if(J(Xe))return(Ve=(H=Xe.describe)===null||H===void 0?void 0:H.call(Xe,p(l)).required)!==null&&Ve!==void 0?Ve:!1;const Je=p(a?.schema);return J(Je)&&(He=(Ke=Je.describe)===null||Ke===void 0?void 0:Ke.call(Je).required)!==null&&He!==void 0?He:!1}),M=n++,R=Ne({id:M,path:l,touched:!1,pending:!1,valid:!0,validated:!!(!((d=pe[I])===null||d===void 0)&&d.length),required:j,initialValue:y,errors:Qt([]),bails:(u=a?.bails)!==null&&u!==void 0?u:!1,label:a?.label,type:a?.type||"default",value:w,multiple:!1,__flags:{pendingUnmount:{[M]:!1},pendingReset:!1},fieldsCount:1,validate:a?.validate,dirty:A(()=>!z(P(w),P(y)))});return g.value.push(R),_.value[I]=R,O(),Z.value[I]&&!pe[I]&&ee(()=>{ve(I,{mode:"silent"})}),Me(l)&&me(l,H=>{O();const Ve=F(w.value);_.value[H]=R,ee(()=>{ie(c,H,Ve)})}),R}const we=St(ft,5),ae=St(ft,5),te=at(async l=>await(l==="silent"?we():ae()),(l,[a])=>{const d=G(S.errorBag.value),y=[...new Set([...G(l.results),...g.value.map(k=>k.path),...d])].sort().reduce((k,h)=>{var w;const I=h,N=D(I)||Le(I),j=((w=l.results[I])===null||w===void 0?void 0:w.errors)||[],M=p(N?.path)||I,R=Yr({errors:j,valid:!j.length},k.results[M]);return k.results[M]=R,R.valid||(k.errors[M]=R.errors[0]),N&&V.value[M]&&delete V.value[M],N?(N.valid=R.valid,a==="silent"||a==="validated-only"&&!N.validated||q(N,R.errors),k):(q(M,j),k)},{valid:l.valid,results:{},errors:{},source:l.source});return l.values&&(y.values=l.values,y.source=l.source),G(y.results).forEach(k=>{var h;const w=D(k);w&&a!=="silent"&&(a==="validated-only"&&!w.validated||q(w,(h=y.results[k])===null||h===void 0?void 0:h.errors))}),y});function le(l){g.value.forEach(l)}function D(l){const a=typeof l=="string"?Ae(l):l;return typeof a=="string"?_.value[a]:a}function Le(l){return g.value.filter(d=>l.startsWith(p(d.path))).reduce((d,u)=>d?u.path.length>d.path.length?u:d:u,void 0)}let ne=[],ce;function Oe(l){return ne.push(l),ce||(ce=ee(()=>{[...ne].sort().reverse().forEach(d=>{kt(c,d)}),ne=[],ce=null})),ce}function qe(l){return function(d,u){return function(k){return k instanceof Event&&(k.preventDefault(),k.stopPropagation()),le(h=>h.touched=!0),s.value=!0,v.value++,ke().then(h=>{const w=F(c);if(h.valid&&typeof d=="function"){const I=F(ue.value);let N=l?I:w;return h.values&&(N=h.source==="schema"?h.values:Object.assign({},N,h.values)),d(N,{evt:k,controlledValues:I,setErrors:W,setFieldError:q,setTouched:$e,setFieldTouched:K,setValues:re,setFieldValue:E,resetForm:We,resetField:ct})}!h.valid&&typeof u=="function"&&u({values:w,evt:k,errors:h.errors,results:h.results})}).then(h=>(s.value=!1,h),h=>{throw s.value=!1,h})}}}const T=qe(!1);T.withControlled=qe(!0);function Ge(l,a){const d=g.value.findIndex(y=>y.path===l&&(Array.isArray(y.id)?y.id.includes(a):y.id===a)),u=g.value[d];if(!(d===-1||!u)){if(ee(()=>{ve(l,{mode:"silent",warn:!1})}),u.multiple&&u.fieldsCount&&u.fieldsCount--,Array.isArray(u.id)){const y=u.id.indexOf(a);y>=0&&u.id.splice(y,1),delete u.__flags.pendingUnmount[a]}(!u.multiple||u.fieldsCount<=0)&&(g.value.splice(d,1),vt(l),O(),delete _.value[l])}}function f(l){G(_.value).forEach(a=>{a.startsWith(l)&&delete _.value[a]}),g.value=g.value.filter(a=>!a.path.startsWith(l)),ee(()=>{O()})}const S={name:i,formId:r,values:c,controlledValues:ue,errorBag:C,errors:Z,schema:Y,submitCount:v,meta:je,isSubmitting:s,isValidating:m,fieldArrays:b,keepValuesOnUnmount:he,validateSchema:P(Y)?te:void 0,validate:ke,setFieldError:q,validateField:ve,setFieldValue:E,setValues:re,setErrors:W,setFieldTouched:K,setTouched:$e,resetForm:We,resetField:ct,handleSubmit:T,useFieldModel:Yt,defineInputBinds:Kt,defineComponentBinds:Ht,defineField:Ye,stageInitialValue:Wt,unsetInitialValue:vt,setFieldInitialValue:Ze,createPathState:ge,getPathState:D,unsetPathValue:Oe,removePathState:Ge,initialValues:de,getAllPathStates:()=>g.value,destroyPath:f,isFieldTouched:Lt,isFieldDirty:Gt,isFieldValid:$t};function E(l,a,d=!0){const u=F(a),y=typeof l=="string"?l:l.path;D(y)||ge(y),ie(c,y,u),d&&ve(y)}function x(l,a=!0){G(c).forEach(d=>{delete c[d]}),G(l).forEach(d=>{E(d,l[d],!1)}),a&&ke()}function re(l,a=!0){Pe(c,l),b.forEach(d=>d&&d.reset()),a&&ke()}function Q(l,a){const d=D(p(l))||ge(l);return A({get(){return d.value},set(u){var y;const k=p(l);E(k,u,(y=p(a))!==null&&y!==void 0?y:!1)}})}function K(l,a){const d=D(l);d&&(d.touched=a)}function Lt(l){const a=D(l);return a?a.touched:g.value.filter(d=>d.path.startsWith(l)).some(d=>d.touched)}function Gt(l){const a=D(l);return a?a.dirty:g.value.filter(d=>d.path.startsWith(l)).some(d=>d.dirty)}function $t(l){const a=D(l);return a?a.valid:g.value.filter(d=>d.path.startsWith(l)).every(d=>d.valid)}function $e(l){if(typeof l=="boolean"){le(a=>{a.touched=l});return}G(l).forEach(a=>{K(a,!!l[a])})}function ct(l,a){var d;const u=a&&"value"in a?a.value:L(de.value,l),y=D(l);y&&(y.__flags.pendingReset=!0),Ze(l,F(u),!0),E(l,u,!1),K(l,(d=a?.touched)!==null&&d!==void 0?d:!1),q(l,a?.errors||[]),ee(()=>{y&&(y.__flags.pendingReset=!1)})}function We(l,a){let d=F(l?.values?l.values:U.value);d=a?.force?d:Pe(U.value,d),d=J(Y)&&B(Y.cast)?Y.cast(d):d,ye(d,{force:a?.force}),le(u=>{var y;u.__flags.pendingReset=!0,u.validated=!1,u.touched=((y=l?.touched)===null||y===void 0?void 0:y[p(u.path)])||!1,E(p(u.path),L(d,p(u.path)),!1),q(p(u.path),void 0)}),a?.force?x(d,!1):re(d,!1),W(l?.errors||{}),v.value=l?.submitCount||0,ee(()=>{ke({mode:"silent"}),le(u=>{u.__flags.pendingReset=!1})})}async function ke(l){const a=l?.mode||"force";if(a==="force"&&le(h=>h.validated=!0),S.validateSchema)return S.validateSchema(a);m.value=!0;const d=await Promise.all(g.value.map(h=>h.validate?h.validate(l).then(w=>({key:p(h.path),valid:w.valid,errors:w.errors,value:w.value})):Promise.resolve({key:p(h.path),valid:!0,errors:[],value:void 0})));m.value=!1;const u={},y={},k={};for(const h of d)u[h.key]={valid:h.valid,errors:h.errors},h.value&&ie(k,h.key,h.value),h.errors.length&&(y[h.key]=h.errors[0]);return{valid:d.every(h=>h.valid),results:u,errors:y,values:k,source:"fields"}}async function ve(l,a){var d;const u=D(l);if(u&&a?.mode!=="silent"&&(u.validated=!0),Y){const{results:y}=await te(a?.mode||"validated-only");return y[l]||{errors:[],valid:!0}}return u?.validate?u.validate(a):(!u&&(d=a?.warn),Promise.resolve({errors:[],valid:!0}))}function vt(l){kt(de.value,l)}function Wt(l,a,d=!1){Ze(l,a),ie(c,l,a),d&&!e?.initialValues&&ie(U.value,l,F(a))}function Ze(l,a,d=!1){ie(de.value,l,F(a)),d&&ie(U.value,l,F(a))}async function ft(){const l=P(Y);if(!l)return{valid:!0,results:{},errors:{},source:"none"};m.value=!0;const a=Te(l)||J(l)?await Ir(l,c):await Ur(l,c,{names:X.value,bailsMap:Be.value});return m.value=!1,a}const Zt=T((l,{evt:a})=>{yr(a)&&a.target.submit()});_t(()=>{if(e?.initialErrors&&W(e.initialErrors),e?.initialTouched&&$e(e.initialTouched),e?.validateOnMount){ke();return}S.validateSchema&&S.validateSchema("silent")}),Me(Y)&&me(Y,()=>{var l;(l=S.validateSchema)===null||l===void 0||l.call(S,"validated-only")}),rt(ot,S);function Ye(l,a){const d=B(a)?void 0:a?.label,u=D(p(l))||ge(l,{label:d}),y=()=>B(a)?a(Ue(u,De)):a||{};function k(){var j;u.touched=!0,((j=y().validateOnBlur)!==null&&j!==void 0?j:Ee().validateOnBlur)&&ve(p(u.path))}function h(){var j;((j=y().validateOnInput)!==null&&j!==void 0?j:Ee().validateOnInput)&&ee(()=>{ve(p(u.path))})}function w(){var j;((j=y().validateOnChange)!==null&&j!==void 0?j:Ee().validateOnChange)&&ee(()=>{ve(p(u.path))})}const I=A(()=>{const j={onChange:w,onInput:h,onBlur:k};return B(a)?Object.assign(Object.assign({},j),a(Ue(u,De)).props||{}):a?.props?Object.assign(Object.assign({},j),a.props(Ue(u,De))):j});return[Q(l,()=>{var j,M,R;return(R=(j=y().validateOnModelUpdate)!==null&&j!==void 0?j:(M=Ee())===null||M===void 0?void 0:M.validateOnModelUpdate)!==null&&R!==void 0?R:!0}),I]}function Yt(l){return Array.isArray(l)?l.map(a=>Q(a,!0)):Q(l)}function Kt(l,a){const[d,u]=Ye(l,a);function y(){u.value.onBlur()}function k(w){const I=ze(w);E(p(l),I,!1),u.value.onInput()}function h(w){const I=ze(w);E(p(l),I,!1),u.value.onChange()}return A(()=>Object.assign(Object.assign({},u.value),{onBlur:y,onInput:k,onChange:h,value:d.value}))}function Ht(l,a){const[d,u]=Ye(l,a),y=D(p(l));function k(h){d.value=h}return A(()=>{const h=B(a)?a(Ue(y,De)):a||{};return Object.assign({[h.model||"modelValue"]:d.value,[`onUpdate:${h.model||"modelValue"}`]:k},u.value)})}const mt=Object.assign(Object.assign({},S),{values:Xt(c),handleReset:()=>We(),submitForm:Zt});return rt(vr,mt),mt}function Wr(e,t,r,i){const n={touched:"some",pending:"some",valid:"every"},s=A(()=>!z(t,P(r)));function m(){const b=e.value;return G(n).reduce((c,g)=>{const V=n[g];return c[g]=b[V](_=>_[g]),c},{})}const v=Ne(m());return Jt(()=>{const b=m();v.touched=b.touched,v.valid=b.valid,v.pending=b.pending}),A(()=>Object.assign(Object.assign({initialValues:P(r)},v),{valid:v.valid&&!G(i.value).length,dirty:s.value}))}function Zr(e,t,r){const i=Mt(r),n=$(i),s=$(F(i));function m(v,b){b?.force?(n.value=F(v),s.value=F(v)):(n.value=Pe(F(n.value)||{},F(v)),s.value=Pe(F(s.value)||{},F(v))),b?.updateFields&&e.value.forEach(c=>{if(c.touched)return;const V=L(n.value,p(c.path));ie(t,p(c.path),F(V))})}return{initialValues:n,originalInitialValues:s,setInitialValues:m}}function Yr(e,t){return t?{valid:e.valid&&t.valid,errors:[...e.errors,...t.errors]}:e}let Tt={script:`
        

        data() {
            return {
                ...
                firstname: '',
                lastname: '',
                username: '',
                city: '',
                state: '',
                zip: '',
                agree: '',
                errors: []
            };
        },
        methods: {
            checkForm: function (e: Event) {
                this.errors = [];
                if (!this.firstname) this.errors.push({ 'firstname': "First Name required." });
                if (!this.lastname) this.errors.push({ 'lastname': "Last Name required." });
                if (!this.username) this.errors.push({ 'username': "UserName required." });
                if (!this.city) this.errors.push({ 'city': "City required." });
                if (!this.state) this.errors.push({ 'state': "State required." });
                if (!this.zip) this.errors.push({ 'zip': "Zip required." });
                if (!this.agree) this.errors.push({ 'agree': "agree required." });
                e.preventDefault();
            },
        }
        
 <form class="row g-3 needs-validation" @submit="checkForm">
                    <div class="col-md-4">
                        <label for="validationCustom01" class="form-label">First name</label>
                        <input type="text" class="form-control" id="validationDefault01" value="Mark" required>
                        <div v-if="errors?.[0]?.firstname" class="invalid-feedback d-block">
                            {{ errors?.[0]?.firstname }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="validationCustom02" class="form-label">Last name</label>
                        <input type="text" class="form-control" id="validationDefault02" value="Otto" required>
                        <div v-if="errors?.[0]?.lastname" class="invalid-feedback d-block">
                            {{ errors?.[0]?.lastname }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="validationCustomUsername" class="form-label">Username</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text" id="inputGroupPrepend">@</span>
                            <input type="text" class="form-control" id="validationCustomUsername"
                                aria-describedby="inputGroupPrepend" v-model="username">
                            <div v-if="errors?.[0]?.username" class="invalid-feedback d-block">
                                {{ errors?.[0]?.username }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="validationCustom03" class="form-label">City</label>
                        <input type="text" class="form-control" id="validationCustom03" v-model="city">
                        <div v-if="errors?.[0]?.city" class="invalid-feedback d-block">
                            {{ errors?.[0]?.city }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="validationCustom04" class="form-label">State</label>
                        <select class="form-select" id="validationCustom04" v-model="state">
                            <option selected disabled value="">Choose...</option>
                            <option>...</option>
                        </select>
                        <div v-if="errors?.[0]?.state" class="invalid-feedback d-block">
                            {{ errors?.[0]?.state }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="validationCustom05" class="form-label">Zip</label>
                        <input type="text" class="form-control" id="validationCustom05" v-model="zip">
                        <div v-if="errors?.[0]?.zip" class="invalid-feedback d-block">
                            {{ errors?.[0]?.zip }}
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="invalidCheck" v-model="agree">
                            <label class="form-check-label" for="invalidCheck">
                                Agree to terms and conditions
                            </label>
                            <div v-if="errors?.[0]?.agree" class="invalid-feedback d-block">
                                You must agree before submitting.
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Submit form</button>
                    </div>
                </form>`},zt={script:`
<form class="row g-3">
                    <div class="col-md-4">
                        <label for="validationDefault01" class="form-label">First name</label>
                        <input type="text" class="form-control" id="validationDefault01" value="Mark" required>
                    </div>
                    <div class="col-md-4">
                        <label for="validationDefault02" class="form-label">Last name</label>
                        <input type="text" class="form-control" id="validationDefault02" value="Otto" required>
                    </div>
                    <div class="col-md-4">
                        <label for="validationDefaultUsername" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text" id="inputGroupPrepend2">@</span>
                            <input type="text" class="form-control" id="validationDefaultUsername"
                                aria-describedby="inputGroupPrepend2" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="validationDefault03" class="form-label">City</label>
                        <input type="text" class="form-control" id="validationDefault03" required>
                    </div>
                    <div class="col-md-3">
                        <label for="validationDefault04" class="form-label">State</label>
                        <select class="form-select" id="validationDefault04" required>
                            <option selected disabled value="">Choose...</option>
                            <option>...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="validationDefault05" class="form-label">Zip</label>
                        <input type="text" class="form-control" id="validationDefault05" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="invalidCheck2" required>
                            <label class="form-check-label" for="invalidCheck2">
                                Agree to terms and conditions
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Submit form</button>
                    </div>
                </form>`},Rt={script:`
 <form class="row g-3">
                            <div class="col-md-4">
                                <label for="validationServer01" class="form-label">First
                                    name</label>
                                <input type="text" class="form-control is-valid" id="validationServer01" value="Mark"
                                    required>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="validationServer02" class="form-label">Last
                                    name</label>
                                <input type="text" class="form-control is-valid" id="validationServer02" value="Otto"
                                    required>
                                <div class="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="validationServerUsername" class="form-label">Username</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" id="inputGroupPrepend3">@</span>
                                    <input type="text" class="form-control is-invalid" id="validationServerUsername"
                                        aria-describedby="inputGroupPrepend3 validationServerUsernameFeedback" required>
                                    <div id="validationServerUsernameFeedback" class="invalid-feedback">
                                        Please choose a username.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="validationServer03" class="form-label">City</label>
                                <input type="text" class="form-control is-invalid" id="validationServer03"
                                    aria-describedby="validationServer03Feedback" required>
                                <div id="validationServer03Feedback" class="invalid-feedback">
                                    Please provide a valid city.
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="validationServer04" class="form-label">State</label>
                                <select class="form-select is-invalid" id="validationServer04"
                                    aria-describedby="validationServer04Feedback" required>
                                    <option selected disabled value="">Choose...</option>
                                    <option>...</option>
                                </select>
                                <div id="validationServer04Feedback" class="invalid-feedback">
                                    Please select a valid state.
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="validationServer05" class="form-label">Zip</label>
                                <input type="text" class="form-control is-invalid" id="validationServer05"
                                    aria-describedby="validationServer05Feedback" required>
                                <div id="validationServer05Feedback" class="invalid-feedback">
                                    Please provide a valid zip.
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input is-invalid" type="checkbox" value=""
                                        id="invalidCheck3" aria-describedby="invalidCheck3Feedback" required>
                                    <label class="form-check-label" for="invalidCheck3">
                                        Agree to terms and conditions
                                    </label>
                                    <div id="invalidCheck3Feedback" class="invalid-feedback">
                                        You must agree before submitting.
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Submit
                                    form</button>
                            </div>
                        </form>`},Kr={script:`

        data() {
            return {
                ...
                firstname: '',
                lastname: '',
                username: '',
                city: '',
                state: '',
                zip: '',
                agree: '',
                errors: []
            };
        },
        methods: {
            checkForm: function (e: Event) {
                this.errors = [];
                if (!this.firstname) this.errors.push({ 'firstname': "First Name required." });
                if (!this.lastname) this.errors.push({ 'lastname': "Last Name required." });
                if (!this.username) this.errors.push({ 'username': "UserName required." });
                if (!this.city) this.errors.push({ 'city': "City required." });
                if (!this.state) this.errors.push({ 'state': "State required." });
                if (!this.zip) this.errors.push({ 'zip': "Zip required." });
                if (!this.agree) this.errors.push({ 'agree': "agree required." });
                e.preventDefault();
            },
        }

<form @submit.prevent="submit">
                            <v-text-field v-model="name.value.value" :counter="10"
                                :error-messages="name.errorMessage.value" label="Name"></v-text-field>

                            <v-text-field v-model="phone.value.value" :counter="7"
                                :error-messages="phone.errorMessage.value" label="Phone Number"></v-text-field>

                            <v-text-field v-model="email.value.value" :error-messages="email.errorMessage.value"
                                label="E-mail"></v-text-field>

                            <v-select v-model="select.value" :items="items"
                                :error-messages="select.errorMessage.value" label="Select"></v-select>

                            <v-checkbox v-model="checkbox.value.value" :error-messages="checkbox.errorMessage.value"
                                value="1" label="Option" type="checkbox"></v-checkbox>

                            <v-btn class="me-4 " color="primary" type="submit">
                                submit
                            </v-btn>

                            <v-btn @click="handleReset" color="error">
                                clear
                            </v-btn>
                        </form>`},Bt={script:`
 <form class="was-validated">
                    <div class="mb-3">
                        <label for="validationTextarea" class="form-label">Textarea</label>
                        <textarea class="form-control is-invalid" id="validationTextarea"
                            placeholder="Required example textarea" required></textarea>
                        <div class="invalid-feedback">
                            Please enter a message in the textarea.
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="validationFormCheck1" required>
                        <label class="form-check-label" for="validationFormCheck1">Check this
                            checkbox</label>
                        <div class="invalid-feedback">Example invalid feedback text</div>
                    </div>

                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="validationFormCheck2" name="radio-stacked"
                            required>
                        <label class="form-check-label" for="validationFormCheck2">Toggle this
                            radio</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="radio" class="form-check-input" id="validationFormCheck3" name="radio-stacked"
                            required>
                        <label class="form-check-label" for="validationFormCheck3">Or toggle
                            this
                            other radio</label>
                        <div class="invalid-feedback">More example invalid feedback text</div>
                    </div>

                    <div class="mb-3">
                        <select class="form-select" required aria-label="select example">
                            <option value="">Open this select menu</option>
                            <option value="1">One</option>
                            <option value="2">Two</option>
                            <option value="3">Three</option>
                        </select>
                        <div class="invalid-feedback">Example invalid select feedback</div>
                    </div>

                    <div class="mb-3">
                        <input type="file" class="form-control" aria-label="file example" required>
                        <div class="invalid-feedback">Example invalid form file feedback</div>
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary" type="submit" disabled>Submit
                            form</button>
                    </div>
                </form>`};const Hr=Object.freeze(Object.defineProperty({__proto__:null,browserDefaultValidation:zt,customValidation:Tt,serversideValidation:Rt,supportedElements:Bt,vutifyValidation:Kr},Symbol.toStringTag,{value:"Module"})),Xr={class:"row"},Jr={class:"col-xl-12"},Qr={class:"col-md-4 mb-3"},ei={key:0,class:"invalid-feedback d-block"},ti={class:"col-md-4 mb-3"},ri={key:0,class:"invalid-feedback d-block"},ii={class:"col-md-4 mb-3"},ai={class:"input-group has-validation"},li={key:0,class:"invalid-feedback d-block"},ni={class:"col-md-6 mb-3"},si={key:0,class:"invalid-feedback d-block"},oi={class:"col-md-3 mb-3"},di={key:0,class:"invalid-feedback d-block"},ui={class:"col-md-3"},ci={key:0,class:"invalid-feedback d-block"},vi={class:"col-12"},fi={class:"form-check"},mi={key:0,class:"invalid-feedback d-block"},bi={class:"col-xl-12"},pi={class:"row"},hi={class:"col-xl-12"},yi={class:"row"},gi={class:"col-xl-12"},ki={class:"col-xl-12"};$(["Item 1","Item 2","Item 3","Item 4"]);const Si={data(){return{dataToPass:{title:"Forms",currentpage:"Validation",activepage:"Validation"},prism:Hr,firstname:"",lastname:"",username:"",city:"",state:"",zip:"",agree:"",errors:[]}},components:{Pageheader:Ft,ShowcodeCard:xe},methods:{checkForm(e){this.errors=[],this.firstname||this.errors.push({firstname:"First Name required."}),this.lastname||this.errors.push({lastname:"Last Name required."}),this.username||this.errors.push({username:"UserName required."}),this.city||this.errors.push({city:"City required."}),this.state||this.errors.push({state:"State required."}),this.zip||this.errors.push({zip:"Zip required."}),this.agree||this.errors.push({agree:"agree required."}),e.preventDefault()}}},Oi=rr({...Si,__name:"validation",setup(e){const{handleSubmit:t,handleReset:r}=$r({validationSchema:{name(i){return i?.length>=2?!0:"Name needs to be at least 2 characters."},phone(i){return i?.length>9&&/[0-9-]+/.test(i)?!0:"Phone number needs to be at least 9 digits."},email(i){return/^[a-z.-]+@[a-z.-]+\.[a-z]+$/i.test(i)?!0:"Must be a valid e-mail."},select(i){return i?!0:"Select an item."},checkbox(i){return i==="1"?!0:"Must be checked."}}});return Fe("name"),Fe("phone"),Fe("email"),Fe("select"),Fe("checkbox"),$(["Item 1","Item 2","Item 3","Item 4"]),t(i=>{alert(JSON.stringify(i,null,2))}),(i,n)=>(oe(),se(lr,null,[Ce(Ft,{propData:i.dataToPass},null,8,["propData"]),o("div",Xr,[o("div",Jr,[Ce(xe,{code:Tt,title:"Custom Validation"},{default:Ie(()=>[o("form",{class:"row needs-validation",onSubmit:n[5]||(n[5]=(...s)=>i.checkForm&&i.checkForm(...s))},[o("div",Qr,[n[6]||(n[6]=o("label",{for:"validationCustom01",class:"form-label"},"First name",-1)),n[7]||(n[7]=o("input",{type:"text",class:"form-control",id:"validationDefault01",value:"Mark",required:""},null,-1)),i.errors?.[0]?.firstname?(oe(),se("div",ei,Se(i.errors?.[0]?.firstname),1)):fe("",!0)]),o("div",ti,[n[8]||(n[8]=o("label",{for:"validationCustom02",class:"form-label"},"Last name",-1)),n[9]||(n[9]=o("input",{type:"text",class:"form-control",id:"validationDefault02",value:"Otto",required:""},null,-1)),i.errors?.[0]?.lastname?(oe(),se("div",ri,Se(i.errors?.[0]?.lastname),1)):fe("",!0)]),o("div",ii,[n[11]||(n[11]=o("label",{for:"validationCustomUsername",class:"form-label"},"Username",-1)),o("div",ai,[n[10]||(n[10]=o("span",{class:"input-group-text",id:"inputGroupPrepend"},"@",-1)),_e(o("input",{type:"text",class:"form-control",id:"validationCustomUsername","aria-describedby":"inputGroupPrepend","onUpdate:modelValue":n[0]||(n[0]=s=>i.username=s)},null,512),[[Qe,i.username]]),i.errors?.[0]?.username?(oe(),se("div",li,Se(i.errors?.[0]?.username),1)):fe("",!0)])]),o("div",ni,[n[12]||(n[12]=o("label",{for:"validationCustom03",class:"form-label"},"City",-1)),_e(o("input",{type:"text",class:"form-control",id:"validationCustom03","onUpdate:modelValue":n[1]||(n[1]=s=>i.city=s)},null,512),[[Qe,i.city]]),i.errors?.[0]?.city?(oe(),se("div",si,Se(i.errors?.[0]?.city),1)):fe("",!0)]),o("div",oi,[n[14]||(n[14]=o("label",{for:"validationCustom04",class:"form-label"},"State",-1)),_e(o("select",{class:"form-select",id:"validationCustom04","onUpdate:modelValue":n[2]||(n[2]=s=>i.state=s)},n[13]||(n[13]=[o("option",{selected:"",disabled:"",value:""},"Choose...",-1),o("option",null,"...",-1)]),512),[[ir,i.state]]),i.errors?.[0]?.state?(oe(),se("div",di,Se(i.errors?.[0]?.state),1)):fe("",!0)]),o("div",ui,[n[15]||(n[15]=o("label",{for:"validationCustom05",class:"form-label"},"Zip",-1)),_e(o("input",{type:"text",class:"form-control",id:"validationCustom05","onUpdate:modelValue":n[3]||(n[3]=s=>i.zip=s)},null,512),[[Qe,i.zip]]),i.errors?.[0]?.zip?(oe(),se("div",ci,Se(i.errors?.[0]?.zip),1)):fe("",!0)]),o("div",vi,[o("div",fi,[_e(o("input",{class:"form-check-input",type:"checkbox",value:"",id:"invalidCheck","onUpdate:modelValue":n[4]||(n[4]=s=>i.agree=s)},null,512),[[ar,i.agree]]),n[16]||(n[16]=o("label",{class:"form-check-label",for:"invalidCheck"}," Agree to terms and conditions ",-1)),i.errors?.[0]?.agree?(oe(),se("div",mi," You must agree before submitting. ")):fe("",!0)])]),n[17]||(n[17]=o("div",{class:"col-12"},[o("button",{class:"btn btn-primary mt-3",type:"submit"},"Submit form")],-1))],32)]),_:1},8,["code"])]),o("div",bi,[Ce(xe,{code:zt,title:"Browser Default Validation"},{default:Ie(()=>n[18]||(n[18]=[o("form",{class:"row g-3"},[o("div",{class:"col-md-4"},[o("label",{for:"validationDefault01",class:"form-label"},"First name"),o("input",{type:"text",class:"form-control",id:"validationDefault01",value:"Mark",required:""})]),o("div",{class:"col-md-4"},[o("label",{for:"validationDefault02",class:"form-label"},"Last name"),o("input",{type:"text",class:"form-control",id:"validationDefault02",value:"Otto",required:""})]),o("div",{class:"col-md-4"},[o("label",{for:"validationDefaultUsername",class:"form-label"},"Username"),o("div",{class:"input-group"},[o("span",{class:"input-group-text",id:"inputGroupPrepend2"},"@"),o("input",{type:"text",class:"form-control",id:"validationDefaultUsername","aria-describedby":"inputGroupPrepend2",required:""})])]),o("div",{class:"col-md-6"},[o("label",{for:"validationDefault03",class:"form-label"},"City"),o("input",{type:"text",class:"form-control",id:"validationDefault03",required:""})]),o("div",{class:"col-md-3"},[o("label",{for:"validationDefault04",class:"form-label"},"State"),o("select",{class:"form-select",id:"validationDefault04",required:""},[o("option",{selected:"",disabled:"",value:""},"Choose..."),o("option",null,"...")])]),o("div",{class:"col-md-3"},[o("label",{for:"validationDefault05",class:"form-label"},"Zip"),o("input",{type:"text",class:"form-control",id:"validationDefault05",required:""})]),o("div",{class:"col-12"},[o("div",{class:"form-check"},[o("input",{class:"form-check-input",type:"checkbox",value:"",id:"invalidCheck2",required:""}),o("label",{class:"form-check-label",for:"invalidCheck2"}," Agree to terms and conditions ")])]),o("div",{class:"col-12"},[o("button",{class:"btn btn-primary",type:"submit"},"Submit form")])],-1)])),_:1,__:[18]},8,["code"])])]),o("div",pi,[o("div",hi,[o("div",yi,[o("div",gi,[Ce(xe,{code:Rt,title:"Server side Validation"},{default:Ie(()=>n[19]||(n[19]=[o("form",{class:"row g-3"},[o("div",{class:"col-md-4"},[o("label",{for:"validationServer01",class:"form-label"},"First name"),o("input",{type:"text",class:"form-control is-valid",id:"validationServer01",value:"Mark",required:""}),o("div",{class:"valid-feedback"}," Looks good! ")]),o("div",{class:"col-md-4"},[o("label",{for:"validationServer02",class:"form-label"},"Last name"),o("input",{type:"text",class:"form-control is-valid",id:"validationServer02",value:"Otto",required:""}),o("div",{class:"valid-feedback"}," Looks good! ")]),o("div",{class:"col-md-4"},[o("label",{for:"validationServerUsername",class:"form-label"},"Username"),o("div",{class:"input-group has-validation"},[o("span",{class:"input-group-text",id:"inputGroupPrepend3"},"@"),o("input",{type:"text",class:"form-control is-invalid",id:"validationServerUsername","aria-describedby":"inputGroupPrepend3 validationServerUsernameFeedback",required:""}),o("div",{id:"validationServerUsernameFeedback",class:"invalid-feedback"}," Please choose a username. ")])]),o("div",{class:"col-md-6"},[o("label",{for:"validationServer03",class:"form-label"},"City"),o("input",{type:"text",class:"form-control is-invalid",id:"validationServer03","aria-describedby":"validationServer03Feedback",required:""}),o("div",{id:"validationServer03Feedback",class:"invalid-feedback"}," Please provide a valid city. ")]),o("div",{class:"col-md-3"},[o("label",{for:"validationServer04",class:"form-label"},"State"),o("select",{class:"form-select is-invalid",id:"validationServer04","aria-describedby":"validationServer04Feedback",required:""},[o("option",{selected:"",disabled:"",value:""},"Choose..."),o("option",null,"...")]),o("div",{id:"validationServer04Feedback",class:"invalid-feedback"}," Please select a valid state. ")]),o("div",{class:"col-md-3"},[o("label",{for:"validationServer05",class:"form-label"},"Zip"),o("input",{type:"text",class:"form-control is-invalid",id:"validationServer05","aria-describedby":"validationServer05Feedback",required:""}),o("div",{id:"validationServer05Feedback",class:"invalid-feedback"}," Please provide a valid zip. ")]),o("div",{class:"col-12"},[o("div",{class:"form-check"},[o("input",{class:"form-check-input is-invalid",type:"checkbox",value:"",id:"invalidCheck3","aria-describedby":"invalidCheck3Feedback",required:""}),o("label",{class:"form-check-label",for:"invalidCheck3"}," Agree to terms and conditions "),o("div",{id:"invalidCheck3Feedback",class:"invalid-feedback"}," You must agree before submitting. ")])]),o("div",{class:"col-12"},[o("button",{class:"btn btn-primary",type:"submit"},"Submit form")])],-1)])),_:1,__:[19]},8,["code"])])])]),o("div",ki,[Ce(xe,{code:Bt,title:"Supported elements"},{default:Ie(()=>n[20]||(n[20]=[o("form",{class:"was-validated"},[o("div",{class:"mb-3"},[o("label",{for:"validationTextarea",class:"form-label"},"Textarea"),o("textarea",{class:"form-control is-invalid",id:"validationTextarea",placeholder:"Required example textarea",required:""}),o("div",{class:"invalid-feedback"}," Please enter a message in the textarea. ")]),o("div",{class:"form-check mb-3"},[o("input",{type:"checkbox",class:"form-check-input",id:"validationFormCheck1",required:""}),o("label",{class:"form-check-label",for:"validationFormCheck1"},"Check this checkbox"),o("div",{class:"invalid-feedback"},"Example invalid feedback text")]),o("div",{class:"form-check"},[o("input",{type:"radio",class:"form-check-input",id:"validationFormCheck2",name:"radio-stacked",required:""}),o("label",{class:"form-check-label",for:"validationFormCheck2"},"Toggle this radio")]),o("div",{class:"form-check mb-3"},[o("input",{type:"radio",class:"form-check-input",id:"validationFormCheck3",name:"radio-stacked",required:""}),o("label",{class:"form-check-label",for:"validationFormCheck3"},"Or toggle this other radio"),o("div",{class:"invalid-feedback"},"More example invalid feedback text")]),o("div",{class:"mb-3"},[o("select",{class:"form-select",required:"","aria-label":"select example"},[o("option",{value:""},"Open this select menu"),o("option",{value:"1"},"One"),o("option",{value:"2"},"Two"),o("option",{value:"3"},"Three")]),o("div",{class:"invalid-feedback"},"Example invalid select feedback")]),o("div",{class:"mb-3"},[o("input",{type:"file",class:"form-control","aria-label":"file example",required:""}),o("div",{class:"invalid-feedback"},"Example invalid form file feedback")]),o("div",{class:"mb-3"},[o("button",{class:"btn btn-primary",type:"submit",disabled:""},"Submit form")])],-1)])),_:1,__:[20]},8,["code"])])])],64))}}),Fi=nr(Oi,[["__scopeId","data-v-7b81b466"]]);export{Fi as default};
