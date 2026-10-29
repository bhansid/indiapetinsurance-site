(function(){
'use strict';
const reduce=matchMedia('(prefers-reduced-motion: reduce)').matches;
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const PAW='<svg viewBox="0 0 24 24" fill="currentColor"><ellipse cx="12" cy="16" rx="5" ry="4.2"/><circle cx="5.5" cy="10" r="2.2"/><circle cx="9.5" cy="6" r="2.2"/><circle cx="14.5" cy="6" r="2.2"/><circle cx="18.5" cy="10" r="2.2"/></svg>';

/* ================= DATA ================= */
const INDIAN_DOGS=['Indie (Indian Pariah)','Indian Spitz','Rajapalayam','Mudhol Hound','Chippiparai','Kombai','Kanni','Rampur Greyhound','Himalayan Sheepdog','Bakharwal','Gaddi Kutta','Bully Kutta','Gull Dong','Gull Terrier','Jonangi','Kaikadi','Pandikona','Ramanadhapuram Mandai','Mahratta Greyhound','Banjara Hound','Vikhan','Tangkhul Hui','Tibetan Mastiff'];
const WORLD_DOGS=['Affenpinscher','Afghan Hound','Airedale Terrier','Akita','Alaskan Malamute','American Bulldog','American Bully','American Cocker Spaniel','American Eskimo Dog','American Pit Bull Terrier','American Staffordshire Terrier','Anatolian Shepherd','Australian Cattle Dog','Australian Shepherd','Australian Terrier','Basenji','Basset Hound','Beagle','Bearded Collie','Bedlington Terrier','Belgian Malinois','Belgian Tervuren','Bernese Mountain Dog','Bichon Frise','Black Russian Terrier','Bloodhound','Border Collie','Border Terrier','Borzoi','Boston Terrier','Bouvier des Flandres','Boxer','Briard','Brittany','Brussels Griffon','Bull Terrier','Bulldog (English)','Bullmastiff','Cairn Terrier','Cane Corso','Cardigan Welsh Corgi','Cavalier King Charles Spaniel','Chesapeake Bay Retriever','Chihuahua','Chinese Crested','Chow Chow','Clumber Spaniel','Cockapoo','Cocker Spaniel (English)','Collie (Rough)','Coton de Tulear','Dachshund','Dalmatian','Dandie Dinmont Terrier','Doberman Pinscher','Dogo Argentino','Dogue de Bordeaux','English Setter','English Springer Spaniel','Eurasier','Field Spaniel','Finnish Spitz','Flat-Coated Retriever','French Bulldog','German Pinscher','German Shepherd','German Shorthaired Pointer','German Spitz','German Wirehaired Pointer','Giant Schnauzer','Golden Retriever','Goldendoodle','Gordon Setter','Great Dane','Great Pyrenees','Greater Swiss Mountain Dog','Greyhound','Havanese','Ibizan Hound','Irish Setter','Irish Terrier','Irish Wolfhound','Italian Greyhound','Jack Russell Terrier','Japanese Chin','Japanese Spitz','Keeshond','Kerry Blue Terrier','Komondor','Kuvasz','Labradoodle','Labrador Retriever','Lakeland Terrier','Leonberger','Lhasa Apso','Maltese','Maltipoo','Manchester Terrier','Mastiff (English)','Miniature Pinscher','Miniature Schnauzer','Neapolitan Mastiff','Newfoundland','Norfolk Terrier','Norwegian Elkhound','Norwich Terrier','Old English Sheepdog','Papillon','Pekingese','Pembroke Welsh Corgi','Pharaoh Hound','Pointer','Pomeranian','Pomsky','Poodle (Miniature)','Poodle (Standard)','Poodle (Toy)','Portuguese Water Dog','Pug','Puggle','Puli','Rhodesian Ridgeback','Rottweiler','Saint Bernard','Saluki','Samoyed','Schipperke','Scottish Terrier','Shar Pei','Shetland Sheepdog','Shiba Inu','Shih Tzu','Siberian Husky','Silky Terrier','Skye Terrier','Smooth Fox Terrier','Soft Coated Wheaten Terrier','Staffordshire Bull Terrier','Standard Schnauzer','Sussex Spaniel','Tibetan Spaniel','Tibetan Terrier','Toy Fox Terrier','Vizsla','Weimaraner','Welsh Springer Spaniel','Welsh Terrier','West Highland White Terrier','Whippet','Wire Fox Terrier','Xoloitzcuintli','Yorkshire Terrier'];
const CATS=['Indian Domestic (Indie)','Bombay','Persian','Himalayan','Siamese','Maine Coon','Bengal','British Shorthair','British Longhair','Ragdoll','Scottish Fold','Sphynx','Russian Blue','Abyssinian','American Shorthair','American Curl','Balinese','Birman','Burmese','Chartreux','Cornish Rex','Devon Rex','Egyptian Mau','Exotic Shorthair','Havana Brown','Japanese Bobtail','Korat','LaPerm','Manx','Munchkin','Nebelung','Norwegian Forest Cat','Ocicat','Oriental Shorthair','Savannah','Selkirk Rex','Siberian','Singapura','Snowshoe','Somali','Tonkinese','Toyger','Turkish Angora','Turkish Van'];
const POP={dog:['Indie (Indian Pariah)','Labrador Retriever','Golden Retriever','German Shepherd','Beagle','Shih Tzu','Pug','Siberian Husky','Rottweiler','Pomeranian','Doberman Pinscher'],cat:['Indian Domestic (Indie)','Persian','Siamese','Bombay','Maine Coon','Bengal']};
const STATES=['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
const SC={'Jammu and Kashmir':'JK','Delhi':'DL','Maharashtra':'MH','Karnataka':'KA','Tamil Nadu':'TN','Telangana':'TS','Punjab':'PB','Haryana':'HR','Uttar Pradesh':'UP','West Bengal':'WB','Gujarat':'GJ','Rajasthan':'RJ','Kerala':'KL','Himachal Pradesh':'HP','Ladakh':'LA','Chandigarh':'CH'};
const DOG_COL={'German Shepherd':['#B9773E','#DDA466','#5B3420','#3E2417'],'Labrador Retriever':['#EBC47E','#FAE4B6','#C99A4E','#C08A45'],'Rottweiler':['#3A2A22','#6B4E3D','#1E1410','#2A1C15'],'Doberman Pinscher':['#3A2A22','#6B4E3D','#1E1410','#2A1C15'],'Pug':['#E6CCA0','#F6E6CA','#BE9C6B','#3A2A20'],'Siberian Husky':['#B9C0CA','#F2F4F7','#7E8794','#5D6570'],'Shih Tzu':['#EADBC4','#FFF6EA','#C2A47C','#9C7A52'],'Beagle':['#D08A47','#F2C08A','#9E5E2C','#6E3A18'],'Indie (Indian Pariah)':['#D9954F','#F3C489','#A9642B','#7E431C'],'Pomeranian':['#F0A64E','#FFD49A','#C97A2C','#B5621C'],'Dalmatian':['#F2F2F0','#FFFFFF','#C9C9C4','#2A2A2A']};
const CAT_COL={'Persian':['#EFE7DB','#FFFFFF','#CDBFAB','#D9CCB8','#FFFFFF'],'Siamese':['#EFE2CB','#FFF7EA','#C9B79A','#7A5A46','#FFF7EA'],'Bombay':['#2E2926','#5A514B','#1A1614','#221D1A','#4A423D'],'Bengal':['#D6964A','#F2C58A','#A36628','#6E4A2C','#FFF1DE'],'Indian Domestic (Indie)':['#EFA24E','#F9CF8E','#C97A2C','#B5621C','#FFF4E4'],'Russian Blue':['#8E9AAB','#C3CCD8','#626E80','#56627A','#AEB8C6'],'Maine Coon':['#9C7656','#C9A27E','#6E4E36','#4A3424','#F2E6D8']};
const DOG_DEF=['#E2A04B','#F7CF8C','#B97526','#9A5A1E'], CAT_DEF=['#A3ABB5','#D3D8DE','#6F7885','#5D6570','#F7F3EC'];

/* ================= ART (original drawings) ================= */
const leg=(x,y,w,h,f,p,k)=>`<g transform="translate(${x} ${y})"><g data-leg="${k}"><rect x="${-w/2}" y="0" width="${w}" height="${h}" rx="${w/2}" fill="${f}"/><ellipse cx="${w*.15}" cy="${h}" rx="${w*.72}" ry="${w*.42}" fill="${p}"/></g></g>`;
function dogArt(p){return `<defs><radialGradient id="${p}B" cx="36%" cy="26%" r="82%"><stop offset="0" stop-color="var(--dog-hi)"/><stop offset=".55" stop-color="var(--dog-fur)"/><stop offset="1" stop-color="var(--dog-shade)"/></radialGradient><radialGradient id="${p}M" cx="40%" cy="30%" r="80%"><stop offset="0" stop-color="#FFFBF2"/><stop offset="1" stop-color="#EFDCC2"/></radialGradient></defs>
<ellipse cx="165" cy="262" rx="104" ry="10" fill="#0D1B3E" opacity=".12"/>
${leg(124,176,22,72,'var(--dog-shade)','var(--dog-shade)','b')}${leg(206,176,22,72,'var(--dog-shade)','var(--dog-shade)','a')}
<g transform="translate(84 150)"><g><path d="M0 0 C-24 -6 -36 -36 -26 -62" stroke="var(--dog-fur)" stroke-width="17" fill="none" stroke-linecap="round"/><animateTransform attributeName="transform" type="rotate" values="-12;18;-12" dur=".7s" repeatCount="indefinite"/></g></g>
<ellipse cx="160" cy="160" rx="88" ry="54" fill="url(#${p}B)"/>
${leg(100,182,24,70,'var(--dog-fur)','#FFF1DD','a')}${leg(222,182,24,70,'var(--dog-fur)','#FFF1DD','b')}
<path d="M190 128 Q216 160 262 148" stroke="#FF7A2F" stroke-width="13" fill="none" stroke-linecap="round"/>
<g transform="translate(222 160)"><circle r="11" fill="#fff" stroke="#2457F5" stroke-width="3"/><rect x="-5" y="-5" width="4" height="4" fill="#0D1B3E"/><rect x="1" y="0" width="4" height="4" fill="#0D1B3E"/></g>
<g transform="translate(238 96)"><circle r="56" fill="url(#${p}B)"/>
<g transform="translate(-28 -42)"><g><path d="M0 0 C-34 2 -40 54 -24 78 C-14 90 2 72 4 50 C6 28 12 10 0 0Z" fill="var(--dog-ear)"/><animateTransform attributeName="transform" type="rotate" values="0;6;0" dur="1.6s" repeatCount="indefinite"/></g></g>
<ellipse cx="32" cy="22" rx="37" ry="27" fill="url(#${p}M)"/><ellipse cx="-6" cy="18" rx="11" ry="7" fill="#F08A7E" opacity=".5"/>
<ellipse cx="14" cy="-14" rx="10" ry="11.5" fill="#1A1210"><animate attributeName="ry" values="11.5;11.5;1;11.5" keyTimes="0;.94;.97;1" dur="4.6s" repeatCount="indefinite"/></ellipse><circle cx="17.5" cy="-18" r="3.8" fill="#fff"/>
<path d="M2 -32 q12 -7 24 0" stroke="var(--dog-shade)" stroke-width="4" fill="none" stroke-linecap="round"/>
<path d="M40 36 q2 18 13 16 q9 -2 4 -18z" fill="#F07B78"/><path d="M62 18 q-2 16 -20 16 q-8 0 -12 -6" stroke="#1A1210" stroke-width="3.6" fill="none" stroke-linecap="round"/>
<ellipse cx="63" cy="8" rx="14" ry="10.5" fill="#1A1210"/><ellipse cx="58" cy="4" rx="4.5" ry="2.6" fill="#fff" opacity=".7"/></g>`}
function catArt(p){return `<defs><radialGradient id="${p}B" cx="36%" cy="26%" r="82%"><stop offset="0" stop-color="var(--cat-hi)"/><stop offset=".55" stop-color="var(--cat-fur)"/><stop offset="1" stop-color="var(--cat-shade)"/></radialGradient></defs>
<ellipse cx="122" cy="262" rx="80" ry="9" fill="#0D1B3E" opacity=".12"/>
${leg(90,186,16,68,'var(--cat-shade)','var(--cat-shade)','b')}${leg(160,186,16,68,'var(--cat-shade)','var(--cat-shade)','a')}
<g transform="translate(54 172)"><g><path d="M0 0 C-30 -4 -48 -50 -24 -82 C-12 -98 6 -90 2 -76" stroke="var(--cat-fur)" stroke-width="14" fill="none" stroke-linecap="round"/><animateTransform attributeName="transform" type="rotate" values="-6;9;-6" dur="2.4s" repeatCount="indefinite"/></g></g>
<ellipse cx="120" cy="178" rx="74" ry="42" fill="url(#${p}B)"/>
<g stroke="var(--cat-stripe)" stroke-width="6" fill="none" stroke-linecap="round" opacity=".55"><path d="M88 140 q7 14 0 26"/><path d="M108 137 q7 16 0 28"/><path d="M128 138 q7 14 0 26"/></g>
${leg(74,190,18,66,'var(--cat-fur)','var(--cat-chest)','a')}${leg(172,190,18,66,'var(--cat-fur)','var(--cat-chest)','b')}
<ellipse cx="172" cy="186" rx="24" ry="26" fill="var(--cat-chest)"/>
<path d="M150 150 q24 22 58 8" stroke="#2457F5" stroke-width="9" fill="none" stroke-linecap="round"/><circle cx="180" cy="166" r="8" fill="#FF7A2F" stroke="#fff" stroke-width="2"/>
<g transform="translate(186 116)">
<path d="M-38 -16 L-32 -72 L-2 -40Z" fill="var(--cat-fur)"/><path d="M-30 -26 L-27 -60 L-10 -40Z" fill="#F4A9A0"/>
<path d="M6 -42 L36 -72 L42 -14Z" fill="var(--cat-fur)"/><path d="M12 -40 L32 -60 L35 -24Z" fill="#F4A9A0"/>
<ellipse rx="48" ry="43" fill="url(#${p}B)"/>
<g stroke="var(--cat-stripe)" stroke-width="4" stroke-linecap="round" opacity=".6"><path d="M-6 -40 v12"/><path d="M5 -42 v14"/><path d="M16 -40 v12"/></g>
<ellipse cx="22" cy="18" rx="21" ry="14" fill="var(--cat-chest)"/><ellipse cx="-14" cy="14" rx="9" ry="5.5" fill="#F08A7E" opacity=".5"/>
<ellipse cx="-2" cy="-4" rx="8.5" ry="10.5" fill="#C9D96A"/><ellipse cx="0" cy="-4" rx="2.8" ry="8" fill="#1A1210"/><circle cx="2.5" cy="-8" r="2.6" fill="#fff"/>
<ellipse cx="30" cy="-4" rx="7.5" ry="10" fill="#C9D96A"/><ellipse cx="32" cy="-4" rx="2.6" ry="7.6" fill="#1A1210"/><circle cx="34" cy="-8" r="2.4" fill="#fff"/>
<ellipse cx="-2" cy="-4" rx="10" ry="0" fill="var(--cat-fur)"><animate attributeName="ry" values="0;0;11;0" keyTimes="0;.92;.96;1" dur="5.4s" repeatCount="indefinite"/></ellipse>
<ellipse cx="30" cy="-4" rx="9" ry="0" fill="var(--cat-fur)"><animate attributeName="ry" values="0;0;11;0" keyTimes="0;.92;.96;1" dur="5.4s" repeatCount="indefinite"/></ellipse>
<path d="M22 9 h11 l-5.5 6.5z" fill="#F08A7E"/><path d="M27.5 15.5 v4 M27.5 19.5 q-5 5 -9.5 1 M27.5 19.5 q5 5 9.5 1" stroke="#1A1210" stroke-width="2.3" fill="none" stroke-linecap="round"/>
<g stroke="#1A1210" stroke-width="1.5" opacity=".45" stroke-linecap="round"><path d="M42 14 l26 -4 M42 19 l26 3 M4 16 l-26 -3 M4 21 l-26 3"/></g></g>`}
// card portrait uses scoped colours so each card keeps its own look
function portraitSVG(sp,breed,pfx){
  const c=sp==='dog'?(DOG_COL[breed]||DOG_DEF):(CAT_COL[breed]||CAT_DEF);
  const vars=sp==='dog'?{'dog-fur':c[0],'dog-hi':c[1],'dog-shade':c[2],'dog-ear':c[3]}:{'cat-fur':c[0],'cat-hi':c[1],'cat-shade':c[2],'cat-stripe':c[3],'cat-chest':c[4]};
  const art=(sp==='dog'?dogArt(pfx):catArt(pfx)).replace(/var\(--([a-z-]+)\)/g,(m,k)=>vars[k]||m);
  return sp==='dog'?`<svg viewBox="70 30 270 250">${art}</svg>`:`<svg viewBox="10 30 250 250">${art}</svg>`;
}

/* ================= QR (decorative pattern) ================= */
function hash(s){let h=2166136261;for(const c of String(s)){h^=c.charCodeAt(0);h=Math.imul(h,16777619)}return h>>>0}
function rng(seed){return()=>{seed=(seed*1664525+1013904223)>>>0;return seed/4294967296}}
function qrMatrix(text,n=25){const r=rng(hash(text)),m=[];for(let y=0;y<n;y++){m[y]=[];for(let x=0;x<n;x++)m[y][x]=r()>.52}
  const f=(ox,oy)=>{for(let y=-1;y<8;y++)for(let x=-1;x<8;x++){const X=ox+x,Y=oy+y;if(X<0||Y<0||X>=n||Y>=n)continue;const ring=Math.max(Math.abs(x-3),Math.abs(y-3));m[Y][X]=x>=0&&y>=0&&x<7&&y<7&&ring!==2}};f(0,0);f(n-7,0);f(0,n-7);return m}
function realQR(text){try{if(!window.qrcode)return null;const q=window.qrcode(0,'M');q.addData(text);q.make();const n=q.getModuleCount(),m=[];for(let y=0;y<n;y++){m[y]=[];for(let x=0;x<n;x++)m[y][x]=q.isDark(y,x)}return m}catch(e){return null}}
const SITE=/^https?:/.test(location.protocol)&&location.hostname!=='localhost'&&!/claude|artifact/.test(location.hostname)?location.origin:'https://indiapetinsurance.com';
const profileURL=id=>id?`${SITE}/p/${id}`:`${SITE}/`;
function qrFor(idNo){return realQR(profileURL(idNo))||qrMatrix(idNo||'x')}
function qrSVG(idNo){const m=qrFor(idNo),n=m.length;let r='';m.forEach((row,y)=>row.forEach((v,x)=>{if(v)r+=`M${x} ${y}h1v1h-1z`}));return `<svg viewBox="0 0 ${n} ${n}" shape-rendering="crispEdges" role="img" aria-label="QR code"><path d="${r}" fill="#0D1B3E"/></svg>`}

/* ================= STATE ================= */
const blank=()=>({species:'dog',name:'',breed:'',gender:'',neutered:'',dobMode:'dob',dob:'',ageY:'',ageM:'',weight:'',colour:'',marks:'',
  lic:'',licNo:'',licAuth:'',kci:'',kciNo:'',chip:'',chipNo:'',
  records:'',rabies:'',core:'',deworm:'',conditions:'',meds:'',
  clinicNone:false,clinic:'',clinicArea:'',doctor:'',doctorPh:'',cOwner:'',cOwnerPh:'',clinicPh:'',
  oName:'',oMobile:'',oVerified:false,oEmail:'',oHouse:'',oLocality:'',oCity:'Jammu',oState:'Jammu and Kashmir',oPin:'',eName:'',eMobile:'',
  kycType:'',kycNo:'',
  pubPhone:true,pubAddr:'locality',pubVet:true,pubAllergy:true,
  cTerms:false,cWa:true,idNo:'',createdAt:''});
let S=blank();
const MEM={photo:'',docs:[],kycFiles:[],angles:{},pw:'',pw2:''}; // files and password stay in memory only
/* ---------- Backend API ---------- */
let ME={user:null,pets:[],api:false};
async function api(path,body){
  const opt={method:body?'POST':'GET',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}};
  if(body instanceof FormData)opt.body=body;else if(body){opt.body=JSON.stringify(body);opt.headers['Content-Type']='application/json'}
  let r;try{r=await fetch('/api/'+path,opt)}catch(e){throw {offline:true}}
  let j=null;try{j=await r.json()}catch(e){}
  if(!j)throw {offline:true,status:r.status};
  if(!r.ok||!j.ok)throw {status:r.status,...j};
  return j}
async function refreshMe(){try{const j=await api('auth.php?action=me');ME={user:j.user,pets:j.pets||[],api:true}}catch(e){ME={user:null,pets:[],api:!e.offline}}
  const nl=document.getElementById('navLogin');if(nl&&ME.user){nl.textContent='My account';nl.href=ME.user.role==='owner'?'/account/':'/admin/'}}
const dataURLtoBlob=u=>{const [h,b]=u.split(','),m=h.match(/:(.*?);/)[1],bin=atob(b),a=new Uint8Array(bin.length);for(let i=0;i<bin.length;i++)a[i]=bin.charCodeAt(i);return new Blob([a],{type:m})};
const SKEY='ipi-id-draft-v1';
function save(){try{const o={...S};delete o.kycNo;localStorage.setItem(SKEY,JSON.stringify(o))}catch(e){}}
function load(){try{const o=JSON.parse(localStorage.getItem(SKEY)||'null');if(o)S={...blank(),...o}}catch(e){}}

/* ================= CARD RENDER ================= */
function ageText(d){
  if(d.dobMode==='dob'&&d.dob){const b=new Date(d.dob),n=new Date();let m=(n.getFullYear()-b.getFullYear())*12+n.getMonth()-b.getMonth();if(n.getDate()<b.getDate())m--;if(m<0||isNaN(m))return '';return m<12?`${m} mo`:`${Math.floor(m/12)} yr${m%12?` ${m%12} mo`:''}`}
  if(d.ageY||d.ageM){return `${d.ageY?d.ageY+' yr':''}${d.ageM?' '+d.ageM+' mo':''}`.trim()}return ''}
function idNumber(d){const sc=SC[d.oState]||'IN';const yy=String(new Date().getFullYear()).slice(2);return `IPI-${sc}-${yy}-${String(hash((d.name||'x')+d.breed+d.oMobile+d.createdAt)%900000+100000)}`}
function cardFront(d,o={}){
  const nm=d.name||(d.species==='cat'?'Your cat':'Your dog');
  const photo=o.photo?`<img src="${o.photo}" alt="">`:portraitSVG(d.species,d.breed,o.pfx||'p');
  const sex=d.gender?(d.gender==='m'?'Male':'Female'):'—';
  const vax=!!(d.rabies||d.records==='yes'), lic=d.lic==='yes', chip=d.chip==='yes', kci=d.kci==='yes';
  return `<div class="idc"><div class="holo"></div>
  <div class="top"><div class="brand"><i>${PAW.replace('currentColor','#2457F5')}</i>IndiaPetInsurance.com</div><span class="kind">${d.species==='cat'?'Cat ID':'Dog ID'}</span></div>
  <div class="photo">${photo}</div>
  <div class="info">
    <div class="nm ${o.flash==='name'?'fill':''}">${esc(nm)}</div>
    <div><div class="lbl">Breed</div><div class="val ${o.flash==='breed'?'fill':''}">${esc(d.breed||'—')}</div></div>
    <div class="row"><div><div class="lbl">Sex</div><div class="val">${sex}${d.neutered==='yes'?' · '+(d.gender==='f'?'Spayed':'Neutered'):''}</div></div><div><div class="lbl">Age</div><div class="val">${esc(ageText(d)||'—')}</div></div></div>
    <div><div class="lbl">ID No.</div><div class="idno">${esc(d.idNo||'IPI-••-••-••••••')}</div></div>
  </div>
  <div class="badges"><span class="bdg ${vax?'on':''}">${vax?'✓':'○'} Vaccinated</span><span class="bdg ${lic?'on':''}">${lic?'✓':'○'} Licensed</span>${kci?'<span class="bdg on">✓ KCI</span>':''}<span class="bdg ${chip?'on':''}">${chip?'✓':'○'} Chip</span></div>
  <div class="qr">${qrSVG(d.idNo||'')}</div><div class="band"></div></div>`}
function cardBack(d){
  const nm=d.name||'This pet';
  const phone=d.pubPhone&&d.oMobile?`+91 ${d.oMobile.slice(0,5)} ${d.oMobile.slice(5)}`:'Hidden by owner';
  const addr=d.pubAddr==='exact'?[d.oHouse,d.oLocality,d.oCity].filter(Boolean).join(', '):[d.oLocality,d.oCity].filter(Boolean).join(', ');
  const mrz=(`IPI<${(d.idNo||'').replace(/-/g,'<')}<<${(nm).toUpperCase().replace(/[^A-Z]/g,'<')}`+'<'.repeat(40)).slice(0,44);
  return `<div class="idc back"><div class="bk">
   <div class="full found">If found, please scan the QR<br>or call ${esc(d.oName?d.oName.split(' ')[0]:'my family')}.</div>
   <div><div class="lbl">Owner phone</div><div class="val">${esc(phone)}</div></div>
   <div><div class="lbl">Area</div><div class="val">${esc(addr||'—')}</div></div>
   <div><div class="lbl">Primary vet</div><div class="val">${d.pubVet?esc(d.doctor?('Dr. '+d.doctor.replace(/^dr\.?\s*/i,'')):(d.clinic||'—')):'Hidden'}</div></div>
   <div><div class="lbl">Clinic</div><div class="val">${d.pubVet?esc(d.clinic||'—'):'Hidden'}</div></div>
   <div class="full"><div class="lbl">Allergies / notes</div><div class="val">${d.pubAllergy?esc(d.conditions||'None declared'):'Hidden'}</div></div>
  </div><div class="mrz">${esc(mrz)}</div><div class="band"></div></div>`}
function sample(sp,name,breed){return {...blank(),species:sp,name,breed,gender:'m',neutered:'yes',dobMode:'age',ageY:'3',rabies:'2026-03-10',records:'yes',lic:'yes',chip:'yes',oName:'Rahul Sharma',oMobile:'9876543210',oLocality:'Gandhi Nagar',oCity:'Jammu',oState:'Jammu and Kashmir',clinic:'Partner clinic, Jammu',doctor:'Mehta',conditions:'Allergic to chicken',idNo:'IPI-JK-26-418302'}}

/* ================= LANDING ================= */
$('#heroCard').innerHTML=cardFront(sample('dog','Bruno','Golden Retriever'),{pfx:'h'});
$('#exFront').innerHTML=cardFront(sample('dog','Bruno','Golden Retriever'),{pfx:'e'});
$('#exBack').innerHTML=cardBack(sample('dog','Bruno','Golden Retriever'));
// rotating names
const NAMES=['Bruno','Sheru','Coco','Simba','Moti','Luna','Tiger','Bella','Oreo','Rocky'];let ni=0;
if(!reduce)setInterval(()=>{ni=(ni+1)%NAMES.length;$('#rot').innerHTML=`<span>${NAMES[ni]}</span>`},2200);
// explainer hotspots
function hot(i){$$('.ex-item').forEach(e=>e.classList.toggle('on',+e.dataset.h===i));$$('.hot').forEach(e=>e.setAttribute('aria-pressed',+e.dataset.h===i));$('#exFlip').classList.remove('flipped')}
$$('[data-h]').forEach(b=>b.addEventListener('click',()=>hot(+b.dataset.h)));
$('#exFlip').addEventListener('click',()=>$('#exFlip').classList.toggle('flipped'));
// breeds marquee
const allB=[...INDIAN_DOGS.map(b=>[b,1]),...WORLD_DOGS.map(b=>[b,0]),...CATS.map(b=>[b,b.includes('Indian')||b==='Bombay'?0:0])];
$('#breedCount').textContent=(INDIAN_DOGS.length+WORLD_DOGS.length+CATS.length)+'';
const half=Math.ceil(allB.length/2), mk=a=>{const h=a.map(([b,i])=>`<span class="${i?'in':''}">${esc(b)}</span>`).join('');return h+h};
const mix=(arr)=>arr.sort((a,b)=>hash(a[0])-hash(b[0]));
$('#mq1').innerHTML=mk(mix(allB.slice(0,half)));$('#mq2').innerHTML=mk(mix(allB.slice(half)));
// QR demo
let unlocked=false;
function profile(anim){const s=$('#qrScreen');
  s.innerHTML=`<div class="profile ${anim?'wz-q anim':''}" style="padding:14px"><div class="hdr"><small>Found · public profile</small><b>Hi, I'm Bruno!</b><span style="font-size:.82rem">Golden Retriever · Male · 3 yrs</span></div>
  <div class="callbtn">📞 Call Rahul (owner)</div><div class="prow"><span>Area</span>Gandhi Nagar, Jammu</div><div class="prow"><span>Vet</span>Dr. Mehta, Partner clinic</div><div class="prow"><span>Note</span>Friendly. Allergic to chicken.</div>
  <div class="locked" id="lockBox">${unlocked?recHTML():`<b>🔒 Medical records</b><span style="font-size:.78rem;color:var(--muted)">Demo: type any 4 digits</span><div class="otp">${[0,1,2,3].map(i=>`<input inputmode="numeric" maxlength="1" aria-label="Digit ${i+1}">`).join('')}</div>`}</div></div>`;
  const ins=[...s.querySelectorAll('.otp input')];ins.forEach((inp,i)=>inp.addEventListener('input',()=>{inp.value=inp.value.replace(/\D/g,'');if(inp.value&&ins[i+1])ins[i+1].focus();if(ins.every(x=>x.value)){unlocked=true;$('#lockBox').innerHTML=recHTML()}}))}
const recHTML=()=>`<b>🔓 Medical records</b><div class="records"><div><span>Anti-rabies</span><span>Mar 2026</span></div><div><span>DHPPi</span><span>Mar 2026</span></div><div><span>Deworming</span><span>Due Nov</span></div></div>`;
$('#scanBtn').onclick=()=>{unlocked=false;$('#qrScreen').innerHTML=`<div class="scan-view dark"><div><div class="scan-box"></div><p style="margin-top:14px">Reading tag…</p></div></div>`;setTimeout(()=>profile(true),reduce?50:1200);$('#scanBtn').textContent='Scan again'};
// counters
let counted=false;new IntersectionObserver(es=>{if(es[0].isIntersecting&&!counted){counted=true;$$('[data-count]').forEach(el=>{const to=+el.dataset.count,suf=el.dataset.suffix||'';if(reduce){el.textContent=to+suf;return}const t0=performance.now();const f=t=>{const k=Math.min(1,(t-t0)/1500);el.textContent=Math.round(to*(1-Math.pow(1-k,3))).toLocaleString('en-IN')+suf;if(k<1)requestAnimationFrame(f)};requestAnimationFrame(f)})}},{threshold:.3}).observe($('#dash'));
// 5+1
$('#paws').innerHTML=[0,1,2,3,4].map(i=>`<div class="pawslot" id="ps${i}">${PAW}</div>`).join('')+`<span class="plusx">+</span><div class="pawslot stray" id="ps5"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.6 1.2 4.3 2.4.7-1.2 2.2-2.4 4.3-2.4 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg></div>`;
let pn=0;const MM=['Tap to protect a dog or cat.','One protected.','Two protected.','Three protected.','Four. One more!','Five protected. A street dog gets treated today ♥'];
$('#protectBtn').onclick=()=>{if(pn>=5){pn=0;$$('.pawslot').forEach(p=>p.classList.remove('on'));$('#mmsg').textContent=MM[0];$('#protectBtn').textContent='Protect one';return}$('#ps'+pn).classList.add('on');pn++;$('#mmsg').textContent=MM[pn];if(pn===5){setTimeout(()=>$('#ps5').classList.add('on'),400);$('#protectBtn').textContent='Start again'}};
$('#vetBtn').onclick=()=>toast('Clinic sign-up opens at launch. For now, WhatsApp us to join the Jammu pilot.');

/* ================= WIZARD ================= */
const STEPS=['species','name','breed','basics','photo','papers','records','clinic','owner','kyc','privacy','review'];
const TITLES={species:'Dog or cat',name:'Name',breed:'Breed',basics:'About',photo:'Photo',papers:'Registration',records:'Health records',clinic:'Primary clinic',owner:'Your details',kyc:'ID proof',privacy:'Privacy',review:'Review'};
let step=0, goal='', flash='', errs={}, view='steps', otpSent=false;
const wiz=$('#wiz'), Q=$('#wzQ');
function firstIncomplete(){for(let i=0;i<STEPS.length;i++){if(Object.keys(validate(STEPS[i])).length)return i}return STEPS.length-1}
function open(spc,g){load();goal=g||'';errs={};
  if(S.idNo&&S.cTerms){view='result'}else{view='steps';if(!S.name){if(spc){S.species=spc;step=1}else step=0}else step=firstIncomplete()}
  wiz.hidden=false;document.body.classList.add('wiz-open');render();setTimeout(()=>{const f=Q.querySelector('input,button');f&&f.focus({preventScroll:true})},60)}
function close(){save();wiz.hidden=true;document.body.classList.remove('wiz-open')}
$$('[data-start]').forEach(b=>b.addEventListener('click',()=>open(b.dataset.start,b.dataset.goal)));
$('#wzClose').onclick=close;
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!wiz.hidden)close()});
$('#wzFlipBtn').onclick=()=>$('#wzFlip').classList.toggle('flipped');
$('#wzSide').addEventListener('click',e=>{if(e.target.closest('.flipper'))$('#wzFlip').classList.toggle('flipped')});

const sp=()=>S.species==='cat'?'cat':'dog';
const NM=()=>S.name||('your '+sp());
const fld=(k,label,inp,hint,opt)=>`<div class="fld"><label for="f_${k}">${label}${opt?' <span class="opt">(optional)</span>':''}</label>${inp}${errs[k]?`<span class="errm">${errs[k]}</span>`:hint?`<span class="hint">${hint}</span>`:''}</div>`;
const txt=(k,ph='',type='text',extra='')=>`<input class="inp ${errs[k]?'err':''}" id="f_${k}" data-k="${k}" type="${type}" placeholder="${esc(ph)}" value="${esc(S[k])}" ${extra}>`;
const choice=(k,opts,cls='choices')=>`<div class="${cls}" role="group">${opts.map(([v,l,s])=>`<button type="button" class="choice" data-set="${k}" data-v="${v}" aria-pressed="${S[k]===v}">${l}${s?`<small>${s}</small>`:''}</button>`).join('')}</div>${errs[k]?`<span class="errm">${errs[k]}</span>`:''}`;
const yn=(k,extra=[])=>choice(k,[['yes','Yes'],['no','No'],...extra]);
const drop=(id,label,sub,multi,accept='image/*,.pdf')=>`<label class="drop" for="${id}"><input type="file" id="${id}" ${multi?'multiple':''} accept="${accept}"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2457F5" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></svg><b>${label}</b><span>${sub}</span></label>`;
const filesHTML=(arr,key)=>arr.length?`<div class="files">${arr.map((f,i)=>`<span class="file">✓ <span>${esc(f.name)}</span><button type="button" data-rm="${key}" data-i="${i}" aria-label="Remove ${esc(f.name)}">×</button></span>`).join('')}</div>`:'';

const VIEWS={
 species:()=>`<span class="qn">Let's start</span><h2>Who's getting the ID today?</h2>
  <div class="species">${['dog','cat'].map(s=>`<button type="button" class="choice" data-set="species" data-v="${s}" aria-pressed="${S.species===s}">${portraitSVG(s,'','sp'+s)}<span>${s==='dog'?'A dog':'A cat'}</span></button>`).join('')}</div>`,
 name:()=>`<span class="qn">Nice to meet you</span><h2>What's your ${sp()}'s name?</h2>
  ${txt('name',sp()==='dog'?'e.g. Sheru':'e.g. Simba','text','maxlength="16" autocomplete="off"').replace('class="inp ','class="inp big ')}${errs.name?`<span class="errm">${errs.name}</span>`:''}
  <div class="pills">${(sp()==='dog'?['Bruno','Sheru','Moti','Rocky','Bella','Coco']:['Simba','Luna','Oreo','Tiger','Kitty','Mishti']).map(n=>`<button type="button" class="pill" data-set="name" data-v="${n}" aria-pressed="${S.name===n}">${n}</button>`).join('')}</div>`,
 breed:()=>{const list=sp()==='dog'?[['Indian breeds',INDIAN_DOGS],['All breeds',WORLD_DOGS],['Other',['Mixed breed','Not sure']]]:[['Cat breeds',CATS],['Other',['Mixed breed','Not sure']]];
  return `<span class="qn">Breed</span><h2>${esc(NM())} is a…</h2>
  <div class="pills">${POP[sp()].map(b=>`<button type="button" class="pill" data-set="breed" data-v="${esc(b)}" aria-pressed="${S.breed===b}">${esc(b.replace(' (Indian Pariah)','').replace(' (Indie)',''))}</button>`).join('')}</div>
  <div class="fld"><label for="bq">Search ${list.reduce((a,g)=>a+g[1].length,0)} breeds</label><input class="inp ${errs.breed?'err':''}" id="bq" placeholder="Type to search, e.g. Rajapalayam" autocomplete="off">${errs.breed?`<span class="errm">${errs.breed}</span>`:''}</div>
  <div class="blist" id="blist" role="listbox" aria-label="Breeds">${list.map(([g,arr])=>`<div class="grp">${g}</div>`+arr.map(b=>`<button type="button" role="option" data-set="breed" data-v="${esc(b)}" aria-selected="${S.breed===b}">${esc(b)}${INDIAN_DOGS.includes(b)?'<small>Indian</small>':''}</button>`).join('')).join('')}</div>
  ${S.breed?`<span class="ok-tag">✓ Selected: ${esc(S.breed)}</span>`:''}`},
 basics:()=>`<span class="qn">A few basics</span><h2>Tell us about ${esc(NM())}.</h2>
  <div class="fld"><span class="lb">Sex</span>${choice('gender',[['m','Male'],['f','Female']])}</div>
  <div class="fld"><span class="lb">${S.gender==='f'?'Spayed':'Neutered / spayed'}?</span>${choice('neutered',[['yes','Yes'],['no','No'],['unsure','Not sure']])}</div>
  <div class="fld"><span class="lb">Age</span><div class="pills"><button type="button" class="pill" data-set="dobMode" data-v="dob" aria-pressed="${S.dobMode==='dob'}">I know the birth date</button><button type="button" class="pill" data-set="dobMode" data-v="age" aria-pressed="${S.dobMode==='age'}">I know roughly</button></div></div>
  ${S.dobMode==='dob'?fld('dob','Date of birth',txt('dob','','date',`max="${new Date().toISOString().slice(0,10)}"`)):`<div class="grid2">${fld('ageY','Years',txt('ageY','3','number','min="0" max="30" inputmode="numeric"'))}${fld('ageM','Months',txt('ageM','0','number','min="0" max="11" inputmode="numeric"'))}</div>`}
  <div class="grid2">${fld('weight','Weight (kg)',txt('weight','e.g. 28','number','min="0" step="0.1" inputmode="decimal"'),'',1)}${fld('colour','Colour',txt('colour','e.g. Golden, white chest'),'',1)}</div>
  ${fld('marks','Identification marks',txt('marks','e.g. Notch on left ear, white paw'),'Helps strangers match the photo',1)}`,
 photo:()=>`<span class="qn">Photo</span><h2>Add ${esc(NM())}'s best face.</h2><p class="sub">A clear, front-facing photo goes on the card. You can skip this and we'll use an illustration.</p>
  ${MEM.photo?`<div style="display:flex;gap:14px;align-items:center"><img src="${MEM.photo}" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:18px"><div><span class="ok-tag">✓ Looking good!</span><br><button type="button" class="btn btn-ghost btn-sm" data-rmphoto style="margin-top:8px">Remove photo</button></div></div>`:drop('f_photo','Upload or take a photo','JPG or PNG. Face the camera, good light.',false,'image/*')}
  <div class="note blue">💡 Insurers usually also need photos from 5 angles. We'll ask for those only if you choose insurance.</div>`,
 papers:()=>`<span class="qn">Registration</span><h2>Is ${esc(NM())} registered anywhere?</h2><p class="sub">Each one adds a verified badge to the card.</p>
  <div class="fld"><span class="lb">Municipal licence (e.g. Jammu Municipal Corporation)</span>${yn('lic',[['applied','Applied']])}</div>
  ${S.lic==='yes'||S.lic==='applied'?`<div class="grid2">${fld('licNo','Licence / token number',txt('licNo','e.g. JMC/DOG/2026/0412'))}${fld('licAuth','Issued by',txt('licAuth','e.g. Jammu Municipal Corporation'))}</div>`:S.lic==='no'?`<div class="note">Many cities require dog owners to register with the municipal corporation. We'll remind you, and the anti-rabies certificate you add next is usually what they ask for.</div>`:''}
  <div class="fld"><span class="lb">Kennel Club of India (KCI) pedigree registration</span>${yn('kci')}</div>
  ${S.kci==='yes'?fld('kciNo','KCI registration number',txt('kciNo','e.g. KCI 12345')):''}
  <div class="fld"><span class="lb">Microchip</span>${yn('chip')}</div>
  ${S.chip==='yes'?fld('chipNo','Microchip number (15 digits)',txt('chipNo','e.g. 985112003456789','text','inputmode="numeric" maxlength="15"')):''}`,
 records:()=>`<span class="qn">Health records</span><h2>Do you have ${esc(NM())}'s vaccination records?</h2>
  ${choice('records',[['yes','Yes, all of them'],['some','Some'],['no','Not with me']])}
  ${S.records&&S.records!=='no'?`${drop('f_docs','Upload vaccination card and reports','Photos or PDFs. Add as many as you like.',true)}${filesHTML(MEM.docs,'docs')}`:S.records==='no'?`<div class="note blue">No problem. Your vet can add them later, or you can upload them from the app.</div>`:''}
  <div class="grid2">${fld('rabies','Last anti-rabies vaccine',txt('rabies','','date'),'',1)}${fld('core',sp()==='dog'?'Last DHPPi / core vaccine':'Last FVRCP / core vaccine',txt('core','','date'),'',1)}</div>
  ${fld('deworm','Last deworming',txt('deworm','','date'),'We use these dates to set up reminders',1)}
  <div class="grid2">${fld('conditions','Allergies or conditions',txt('conditions','e.g. Skin allergy, chicken'),'',1)}${fld('meds','Current medicines',txt('meds','e.g. None'),'',1)}</div>`,
 clinic:()=>`<span class="qn">Primary clinic</span><h2>Who's ${esc(NM())}'s vet?</h2><p class="sub">Reminders will go out in this clinic's name.</p>
  <label class="check"><input type="checkbox" data-chk="clinicNone" ${S.clinicNone?'checked':''}> We don't have a regular vet yet. Suggest a partner clinic near me.</label>
  ${S.clinicNone?'':`<div class="grid2">${fld('clinic','Clinic / hospital name',txt('clinic','e.g. City Vet Clinic'))}${fld('clinicArea','Area',txt('clinicArea','e.g. Gandhi Nagar, Jammu'),'',1)}</div>
  <div class="grid2">${fld('doctor','Doctor name',txt('doctor','e.g. Dr. Mehta'))}${fld('doctorPh','Doctor mobile',`<div class="pfx"><span>+91</span>${txt('doctorPh','10 digits','tel','inputmode="numeric" maxlength="10"')}</div>`,'',1)}</div>
  <div class="grid2">${fld('cOwner','Clinic owner name',txt('cOwner','e.g. Dr. Sharma'),'',1)}${fld('cOwnerPh','Clinic owner mobile',`<div class="pfx"><span>+91</span>${txt('cOwnerPh','10 digits','tel','inputmode="numeric" maxlength="10"')}</div>`,'',1)}</div>
  ${fld('clinicPh','Clinic phone / reception',`<div class="pfx"><span>+91</span>${txt('clinicPh','10 digits','tel','inputmode="numeric" maxlength="10"')}</div>`,'',1)}`}`,
 owner:()=>`<span class="qn">You</span><h2>Who is ${esc(NM())}'s family?</h2>
  ${fld('oName','Your full name',txt('oName','As on your ID proof','text','autocomplete="name"'))}
  <div class="fld"><label for="f_oMobile">Mobile (WhatsApp)</label><div class="sendotp"><div class="pfx" style="flex:1;min-width:200px"><span>+91</span>${txt('oMobile','10 digits','tel','inputmode="numeric" maxlength="10" autocomplete="tel-national"')}</div>${S.oVerified?'<span class="ok-tag">✓ Verified</span>':`<button type="button" class="btn btn-ghost btn-sm" id="sendOtp">${otpSent?'Resend OTP':'Send OTP'}</button>`}</div>
  ${errs.oMobile?`<span class="errm">${errs.oMobile}</span>`:''}${otpSent&&!S.oVerified?`<div class="otp" id="ownOtp" style="margin-top:8px">${[0,1,2,3].map(i=>`<input inputmode="numeric" maxlength="1" aria-label="OTP digit ${i+1}">`).join('')}</div><span class="hint">Demo: type any 4 digits.</span>`:''}</div>
  ${fld('oEmail','Email',txt('oEmail','you@example.com','email','autocomplete="email"'),'',1)}
  ${fld('oHouse','House / flat, street',txt('oHouse','e.g. 14, Lane 3','text','autocomplete="address-line1"'),'',1)}
  <div class="grid2">${fld('oLocality','Locality',txt('oLocality','e.g. Gandhi Nagar'))}${fld('oCity','City',txt('oCity','e.g. Jammu','text','autocomplete="address-level2"'))}</div>
  <div class="grid2">${fld('oState','State / UT',`<select class="inp" id="f_oState" data-k="oState">${STATES.map(s=>`<option ${S.oState===s?'selected':''}>${s}</option>`).join('')}</select>`)}${fld('oPin','PIN code',txt('oPin','6 digits','text','inputmode="numeric" maxlength="6" autocomplete="postal-code"'))}</div>
  ${ME.user?`<div class="note blue">✓ This ID will be saved to your account (${esc(ME.user.name)}).</div>`:`<div class="pwrow"><div class="fld"><label for="f_pw">Create a password</label><input class="inp ${errs.pw?'err':''}" id="f_pw" data-mem="pw" type="password" autocomplete="new-password" placeholder="8+ characters" value="${esc(MEM.pw)}">${errs.pw?`<span class="errm">${errs.pw}</span>`:'<span class="hint">Log in later at /account to manage the ID</span>'}</div><div class="fld"><label for="f_pw2">Repeat password</label><input class="inp ${errs.pw2?'err':''}" id="f_pw2" data-mem="pw2" type="password" autocomplete="new-password" value="${esc(MEM.pw2)}">${errs.pw2?`<span class="errm">${errs.pw2}</span>`:''}</div></div>`}
  <div class="grid2">${fld('eName','Emergency contact',txt('eName','Family member or friend'),'',1)}${fld('eMobile','Their mobile',`<div class="pfx"><span>+91</span>${txt('eMobile','10 digits','tel','inputmode="numeric" maxlength="10"')}</div>`,'',1)}</div>`,
 kyc:()=>`<span class="qn">ID proof</span><h2>Quick owner verification.</h2><p class="sub">Insurers need KYC before a policy, and municipal licences need owner ID. You can skip this now.</p>
  ${fld('kycType','ID type',`<select class="inp" id="f_kycType" data-k="kycType"><option value="">Choose one</option>${['Aadhaar card','PAN card','Driving licence','Voter ID','Passport'].map(t=>`<option ${S.kycType===t?'selected':''}>${t}</option>`).join('')}</select>`,'',1)}
  ${S.kycType?fld('kycNo',S.kycType+' number',txt('kycNo',S.kycType==='Aadhaar card'?'XXXX XXXX XXXX':'','text','autocomplete="off"'),S.kycType==='Aadhaar card'?'Only the last 4 digits will ever be shown. You may also upload a masked Aadhaar.':'',1):''}
  ${S.kycType?drop('f_kyc','Upload front and back','Photo or PDF',true)+filesHTML(MEM.kycFiles,'kycFiles'):''}
  <div class="note blue">🔒 In this preview nothing leaves your device and the ID number isn't saved. At launch, documents are encrypted and shared only with the insurer you choose.</div>`,
 privacy:()=>`<span class="qn">Privacy</span><h2>What can a stranger see when they scan ${esc(NM())}'s tag?</h2>
  <div><div class="sw"><div><b>My phone number</b><span>So a finder can call you</span></div><button type="button" class="tgl" role="switch" data-tgl="pubPhone" aria-checked="${S.pubPhone}" aria-label="Show phone"></button></div>
  <div class="sw"><div><b>My address</b><span>${S.pubAddr==='exact'?'Exact address shown':'Only locality and city shown'}</span></div><div class="pills"><button type="button" class="pill" data-set="pubAddr" data-v="locality" aria-pressed="${S.pubAddr==='locality'}">Locality</button><button type="button" class="pill" data-set="pubAddr" data-v="exact" aria-pressed="${S.pubAddr==='exact'}">Exact</button></div></div>
  <div class="sw"><div><b>Vet and clinic</b><span>Useful in an emergency</span></div><button type="button" class="tgl" role="switch" data-tgl="pubVet" aria-checked="${S.pubVet}" aria-label="Show vet"></button></div>
  <div class="sw"><div><b>Allergies and notes</b><span>e.g. "Allergic to chicken"</span></div><button type="button" class="tgl" role="switch" data-tgl="pubAllergy" aria-checked="${S.pubAllergy}" aria-label="Show allergies"></button></div>
  <div class="sw"><div><b>Medical records</b><span>Always locked. Needs an OTP from you.</span></div><span class="ok-tag">🔒 Locked</span></div></div>
  <label class="check"><input type="checkbox" data-chk="cTerms" ${S.cTerms?'checked':''}> I confirm these details are correct and agree to the Terms and Privacy Policy.</label>${errs.cTerms?`<span class="errm">${errs.cTerms}</span>`:''}
  <label class="check"><input type="checkbox" data-chk="cWa" ${S.cWa?'checked':''}> Send vaccine and deworming reminders on WhatsApp.</label>`,
 review:()=>{const rows=[['name','Name & breed',`${S.name}, ${S.breed}`],['basics','About',[S.gender==='m'?'Male':S.gender==='f'?'Female':'',ageText(S),S.weight&&S.weight+' kg',S.colour].filter(Boolean).join(' · ')||'—'],['papers','Registration',[S.lic==='yes'?'Municipal licence '+(S.licNo||''):S.lic==='applied'?'Licence applied':'No licence',S.kci==='yes'?'KCI '+S.kciNo:'',S.chip==='yes'?'Chip '+S.chipNo:''].filter(Boolean).join(' · ')],['records','Health',`${MEM.docs.length} document(s)${S.rabies?' · Rabies '+fmt(S.rabies):''}${S.conditions?' · '+S.conditions:''}`],['clinic','Clinic',S.clinicNone?'Suggest a partner clinic':[S.clinic,S.doctor&&'Dr. '+S.doctor.replace(/^dr\.?\s*/i,''),S.doctorPh&&'+91 '+S.doctorPh].filter(Boolean).join(' · ')||'—'],['owner','Owner',`${S.oName} · +91 ${S.oMobile}${S.oVerified?' ✓':''} · ${[S.oLocality,S.oCity,S.oPin].filter(Boolean).join(', ')}`],['kyc','ID proof',S.kycType?`${S.kycType}${MEM.kycFiles.length?' · uploaded':''}`:'Skipped (needed for insurance)']];
  return `<span class="qn">Almost there</span><h2>Check ${esc(NM())}'s details.</h2>${loginBox&&!ME.user?`<div class="rv" style="border-color:var(--blue);display:grid;gap:10px;grid-template-columns:1fr"><b>You already have an account</b><p>Log in to add ${esc(NM())} to it.</p><div class="grid2"><input class="inp" id="inUser" value="${esc(S.oMobile)}" placeholder="Mobile or email" autocomplete="username"><input class="inp" id="inPass" type="password" placeholder="Password" autocomplete="current-password"></div><span class="errm" id="inErr"></span><div><button class="btn btn-blue btn-sm" type="button" id="inLogin">Log in and create ID</button> <a href="/account/" class="hint">Forgot password?</a></div></div>`:''}<div class="review">${rows.map(([s,t,v])=>`<div class="rv"><b>${t}</b><p>${esc(v)}</p><button type="button" data-goto="${s}">Edit</button></div>`).join('')}</div>`}
};
const fmt=d=>{if(!d)return '';const x=new Date(d);return isNaN(x)?d:x.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'})};

function validate(id){const e={},ph=/^[6-9]\d{9}$/;
  if(id==='name'&&!S.name.trim())e.name='Please add a name.';
  if(id==='breed'&&!S.breed)e.breed='Pick a breed, or choose "Mixed breed" or "Not sure".';
  if(id==='basics'){if(!S.gender)e.gender='Please choose one.';if(S.dobMode==='dob'&&S.dob&&new Date(S.dob)>new Date())e.dob='Birth date can\'t be in the future.'}
  if(id==='papers'&&S.chip==='yes'&&S.chipNo&&!/^\d{15}$/.test(S.chipNo))e.chipNo='Microchip numbers are 15 digits.';
  if(id==='clinic'&&!S.clinicNone){if(!S.clinic.trim())e.clinic='Add the clinic name, or tick the box above.';['doctorPh','cOwnerPh','clinicPh'].forEach(k=>{if(S[k]&&!ph.test(S[k]))e[k]='Enter a valid 10-digit mobile.'})}
  if(id==='owner'){if(!S.oName.trim())e.oName='Please add your name.';if(!ph.test(S.oMobile))e.oMobile='Enter a valid 10-digit Indian mobile.';if(S.oEmail&&!/^\S+@\S+\.\S+$/.test(S.oEmail))e.oEmail='That email looks incomplete.';if(!S.oLocality.trim())e.oLocality='Add your locality.';if(!S.oCity.trim())e.oCity='Add your city.';if(!/^\d{6}$/.test(S.oPin))e.oPin='PIN codes are 6 digits.';if(S.eMobile&&!ph.test(S.eMobile))e.eMobile='Enter a valid 10-digit mobile.';
    if(!ME.user&&ME.api){if(MEM.pw.length<8)e.pw='Use at least 8 characters.';else if(MEM.pw!==MEM.pw2)e.pw2='Passwords don\'t match.'}}
  if(id==='kyc'&&S.kycType==='Aadhaar card'&&S.kycNo&&!/^\d{12}$/.test(S.kycNo.replace(/\s/g,'')))e.kycNo='Aadhaar numbers have 12 digits.';
  if(id==='kyc'&&S.kycType==='PAN card'&&S.kycNo&&!/^[A-Z]{5}\d{4}[A-Z]$/i.test(S.kycNo))e.kycNo='PAN looks like ABCDE1234F.';
  if(id==='privacy'&&!S.cTerms)e.cTerms='Please confirm to create the ID.';
  return e}
function pct(){const f=['name','breed','gender','neutered','colour','lic','records','clinic','oName','oMobile','oPin','cTerms'];let n=f.filter(k=>S[k]).length+(MEM.photo?1:0)+(S.dob||S.ageY?1:0)+(MEM.docs.length?1:0)+(S.kycType?1:0);return Math.min(100,Math.round(n/(f.length+4)*100))}
function updateCard(){
  const d={...S,idNo:S.idNo||''};
  $('#wzFront').innerHTML=cardFront(d,{photo:MEM.photo,flash,pfx:'w'});$('#wzBackCard').innerHTML=cardBack(d);flash='';
  const p=view==='result'?100:pct();$('#wzPct').textContent=p+'%';
  const pi=view==='result'?STEPS.length:step;$('#wzBar').style.width=(view==='result'?100:Math.max(4,(step/STEPS.length)*100))+'%';
  $('#wzLabel').textContent=view==='result'?`${S.name}'s ID is ready`:`Step ${step+1} of ${STEPS.length} · ${TITLES[STEPS[step]]}`;
}
function render(anim=true){
  if(view==='result'){renderResult();return}
  $('#wzBody').style.gridTemplateColumns='';$('#wzBody').style.gridTemplateRows='';$('#wzSide').hidden=false;$('#wzFoot').hidden=false;Q.removeAttribute('style');
  const id=STEPS[step];Q.className='wz-q'+(anim?' anim':'');Q.innerHTML=VIEWS[id]();
  $('#wzBack').style.visibility=step===0?'hidden':'visible';
  $('#wzNext').textContent=id==='review'?`Create ${S.name||'the'} ID ✨`:(['photo','papers','records','kyc'].includes(id)&&!hasInput(id)?'Skip for now':'Continue');
  bind();updateCard();
}
function hasInput(id){return id==='photo'?!!MEM.photo:id==='papers'?!!(S.lic||S.kci||S.chip):id==='records'?!!(S.records||S.rabies):id==='kyc'?!!S.kycType:true}
function bind(){
  Q.querySelectorAll('[data-k]').forEach(el=>el.addEventListener('input',()=>{let v=el.value;if(el.type==='tel'||['oPin','chipNo'].includes(el.dataset.k))v=v.replace(/\D/g,'');if(el.dataset.k==='kycNo'&&S.kycType==='PAN card')v=v.toUpperCase();if(v!==el.value)el.value=v;S[el.dataset.k]=v;if(el.dataset.k==='oMobile'){S.oVerified=false}delete errs[el.dataset.k];el.classList.remove('err');if(el.dataset.k==='name')flash='name';save();updateCard();if(STEPS[step]==='photo'||el.tagName==='SELECT'&&el.dataset.k==='kycType')render(false);if(el.dataset.k!=='oMobile')refreshNext()}));
  Q.querySelectorAll('[data-set]').forEach(b=>b.addEventListener('click',()=>{S[b.dataset.set]=b.dataset.v;delete errs[b.dataset.set];if(b.dataset.set==='breed')flash='breed';if(b.dataset.set==='species'){S.breed=''}save();
    if(b.dataset.set==='species'||b.dataset.set==='name'&&STEPS[step]==='name'){render(false);if(b.dataset.set==='species')setTimeout(next,250);return}
    if(b.dataset.set==='breed'){render(false);setTimeout(next,350);return}render(false)}));
  Q.querySelectorAll('[data-mem]').forEach(el=>el.addEventListener('input',()=>{MEM[el.dataset.mem]=el.value;delete errs[el.dataset.mem];el.classList.remove('err')}));
  const lg=$('#inLogin');if(lg)lg.onclick=inlineLogin;
  Q.querySelectorAll('[data-tgl]').forEach(b=>b.addEventListener('click',()=>{S[b.dataset.tgl]=!S[b.dataset.tgl];save();render(false)}));
  Q.querySelectorAll('[data-chk]').forEach(c=>c.addEventListener('change',()=>{S[c.dataset.chk]=c.checked;delete errs[c.dataset.chk];save();render(false)}));
  Q.querySelectorAll('[data-goto]').forEach(b=>b.addEventListener('click',()=>{step=STEPS.indexOf(b.dataset.goto);render()}));
  Q.querySelectorAll('[data-rm]').forEach(b=>b.addEventListener('click',()=>{MEM[b.dataset.rm].splice(+b.dataset.i,1);render(false)}));
  const rp=Q.querySelector('[data-rmphoto]');if(rp)rp.onclick=()=>{MEM.photo='';render(false)};
  const bq=$('#bq');if(bq){bq.addEventListener('input',()=>{const q=bq.value.toLowerCase().trim();$('#blist').querySelectorAll('button').forEach(b=>b.hidden=q&&!b.dataset.v.toLowerCase().includes(q));$('#blist').querySelectorAll('.grp').forEach(g=>{let n=g.nextElementSibling,any=false;while(n&&!n.classList.contains('grp')){if(!n.hidden)any=true;n=n.nextElementSibling}g.hidden=!any})})}
  const ph=$('#f_photo');if(ph)ph.addEventListener('change',()=>readImg(ph.files[0],u=>{MEM.photo=u;flash='';render(false)}));
  const dc=$('#f_docs');if(dc)dc.addEventListener('change',()=>{MEM.docs.push(...[...dc.files].map(f=>({name:f.name,file:f})));if(!S.records)S.records='yes';render(false)});
  const ky=$('#f_kyc');if(ky)ky.addEventListener('change',()=>{MEM.kycFiles.push(...[...ky.files].map(f=>({name:f.name,file:f})));render(false)});
  Q.querySelectorAll('.drop').forEach(d=>{['dragover','dragenter'].forEach(e=>d.addEventListener(e,()=>d.classList.add('over')));['dragleave','drop'].forEach(e=>d.addEventListener(e,()=>d.classList.remove('over')))});
  const so=$('#sendOtp');if(so)so.onclick=()=>{if(!/^[6-9]\d{9}$/.test(S.oMobile)){errs.oMobile='Enter a valid 10-digit Indian mobile first.';render(false);return}otpSent=true;render(false);toast('Demo: OTP "sent". Type any 4 digits.');setTimeout(()=>{const f=Q.querySelector('#ownOtp input');f&&f.focus()},50)};
  const oo=Q.querySelectorAll('#ownOtp input');oo.forEach((inp,i)=>inp.addEventListener('input',()=>{inp.value=inp.value.replace(/\D/g,'');if(inp.value&&oo[i+1])oo[i+1].focus();if([...oo].every(x=>x.value)){S.oVerified=true;save();render(false);toast('Mobile verified ✓')}}));
  Q.querySelectorAll('input.inp').forEach(i=>i.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();next()}}));
}
function refreshNext(){const id=STEPS[step];if(['photo','papers','records','kyc'].includes(id))$('#wzNext').textContent=hasInput(id)?'Continue':'Skip for now'}
function readImg(file,cb){if(!file)return;const r=new FileReader();r.onload=()=>{const img=new Image();img.onload=()=>{const c=document.createElement('canvas'),s=Math.min(1,640/Math.max(img.width,img.height));c.width=img.width*s;c.height=img.height*s;c.getContext('2d').drawImage(img,0,0,c.width,c.height);cb(c.toDataURL('image/jpeg',.85))};img.src=r.result};r.readAsDataURL(file)}
function next(){const id=STEPS[step];errs=validate(id);if(Object.keys(errs).length){render(false);const f=Q.querySelector('.err,.errm');f&&f.scrollIntoView({block:'center',behavior:'smooth'});return}
  if(id==='review'){createID();return}step++;render();$('#wzPane').scrollTop=0}
$('#wzNext').onclick=next;$('#wzBack').onclick=()=>{if(step>0){step--;errs={};render();$('#wzPane').scrollTop=0}};

/* ================= RESULT ================= */
let orderStep='', insStep='', order={prod:'combo'}, ins={plan:'complete',si:'50000',ill:'',pre:''};
let busy=false,loginBox=false;
async function createID(){
  if(busy)return;busy=true;const nb=$('#wzNext');nb.disabled=true;nb.textContent='Creating ID…';
  S.createdAt=S.createdAt||new Date().toISOString();
  const fd=new FormData();fd.append('data',JSON.stringify(S));if(!ME.user)fd.append('password',MEM.pw);
  if(MEM.photo)fd.append('photo',dataURLtoBlob(MEM.photo),'photo.jpg');
  MEM.docs.forEach(d=>d.file&&fd.append('docs[]',d.file,d.name));MEM.kycFiles.forEach(d=>d.file&&fd.append('kyc[]',d.file,d.name));
  try{const j=await api('pet.php',fd);S.idNo=j.id_no;S.saved=true;S.kycNo='';MEM.pw=MEM.pw2='';await refreshMe();finish()}
  catch(e){
    if(e.offline||e.code==='not_installed'){S.idNo=idNumber(S);S.saved=false;finish();toast('Preview mode: this ID was not saved to a server.');}
    else if(e.code==='account_exists'){loginBox=true;render(false);toast(e.error)}
    else if(e.fields){errs=e.fields;const order=['species','name','breed','basics','papers','records','clinic','owner','kyc','privacy'];const where={name:'name',breed:'breed',gender:'basics',oName:'owner',oMobile:'owner',oEmail:'owner',oPin:'owner',password:'owner',cTerms:'privacy',doctorPh:'clinic',cOwnerPh:'clinic',clinicPh:'clinic',eMobile:'owner'};const f=Object.keys(e.fields)[0];if(f==='password')errs.pw=e.fields.password;step=STEPS.indexOf(where[f]||'review');render();toast(e.error)}
    else{toast(e.error||'Something went wrong. Please try again.')}
  }finally{busy=false;const b=$('#wzNext');if(b)b.disabled=false}}
function finish(){save();view='result';render();confetti()}
async function inlineLogin(){const l=$('#inUser').value,p=$('#inPass').value,er=$('#inErr');er.textContent='';
  try{await api('auth.php',{action:'login',login:l,password:p});await refreshMe();loginBox=false;toast('Logged in as '+ME.user.name);createID()}catch(e){er.textContent=e.error||'Could not log in.'}}
function reminders(){const out=[],add=(d,m)=>{const x=new Date(d);x.setMonth(x.getMonth()+m);return x},now=new Date();
  const nm=S.name;
  if(S.rabies)out.push(['💉',`Anti-rabies booster`,add(S.rabies,12)]);else out.push(['💉','Add anti-rabies date',null]);
  if(S.core)out.push(['🛡️',sp()==='dog'?'DHPPi booster':'FVRCP booster',add(S.core,12)]);
  if(S.deworm)out.push(['🪱','Deworming',add(S.deworm,3)]);else out.push(['🪱','Deworming check',add(now,0)]);
  out.push(['🩺','Annual health check',add(S.createdAt||now,12)]);
  if(S.lic==='yes')out.push(['📄','Municipal licence renewal',add(S.createdAt||now,12)]);
  return out.map(([i,t,d])=>{const due=d?Math.round((d-now)/864e5):null;return `<div class="rm"><i>${i}</i><div><b>${t}</b><span>${d?fmt(d.toISOString().slice(0,10)):'We\'ll ask your vet'}${S.cWa?' · WhatsApp reminder from '+(S.clinic||'your clinic'):''}</span></div>${due===null?'<span class="st due">Missing</span>':due<0?'<span class="st due">Overdue</span>':due<30?`<span class="st due">In ${due} days</span>`:'<span class="st ok">Scheduled</span>'}</div>`}).join('')}
const PRODS={card:['Smart PVC ID card','Wallet-size, waterproof, QR on the back','card'],tag:['Steel QR collar tag','Laser-engraved, scratch-proof','tag'],combo:['Card + steel tag','Best for lost-and-found','combo']};
function prodArt(k){const t=`<g transform="translate(70 18)"><circle cx="20" cy="4" r="5" fill="none" stroke="#8896B8" stroke-width="3"/><path d="M20 10c18 0 22 14 22 24 0 16-10 26-22 26S-2 50-2 34C-2 24 2 10 20 10z" fill="#C9D2E6"/><rect x="8" y="24" width="24" height="24" rx="4" fill="#fff"/><rect x="11" y="27" width="7" height="7" fill="#0D1B3E"/><rect x="22" y="37" width="7" height="7" fill="#0D1B3E"/></g>`,c=`<g transform="translate(10 28) rotate(-6)"><rect width="96" height="60" rx="8" fill="#1739B0"/><rect x="6" y="10" width="22" height="28" rx="4" fill="#FFC9A3"/><rect x="34" y="12" width="40" height="6" rx="3" fill="#fff"/><rect x="34" y="24" width="28" height="4" rx="2" fill="#fff" opacity=".6"/><rect x="72" y="36" width="18" height="18" rx="2" fill="#fff"/><rect x="0" y="56" width="96" height="4" fill="#FF7A2F"/></g>`;return `<svg viewBox="0 0 140 100" width="140" height="100">${k==='card'?c.replace('translate(10 28)','translate(22 20)'):k==='tag'?t.replace('translate(70 18)','translate(50 14)'):c+t}</svg>`}
function renderResult(){
  $('#wzSide').hidden=true;$('#wzFoot').hidden=true;$('#wzBody').style.gridTemplateColumns='1fr';$('#wzBody').style.gridTemplateRows='1fr';
  updateCard();
  const d={...S};const nm=esc(S.name);
  Q.className='wz-q';Q.style.maxWidth='none';Q.style.padding='0';
  Q.innerHTML=`<div class="res">
   <div class="res-hero">
     <div><span class="eyebrow" style="color:var(--green)">✓ ID created</span><h2 style="margin-top:10px">${nm} is officially somebody.</h2><p class="lede" style="margin-top:12px">Save the card, get it printed for the collar, and set up protection, all from here.</p>
       <div class="idnum" id="idSlot">${esc(S.idNo)}</div>
       ${S.saved?`<div class="saved"><span class="ok-tag">✓ Saved to your account</span> · <a href="${profileURL(S.idNo)}" target="_blank" rel="noopener">Open QR profile</a> · <a href="/account/">My account</a></div>`:`<div class="saved">Preview only. This ID is not saved yet.</div>`}
       <div class="acts"><button class="btn btn-blue" type="button" id="dlBtn">⬇ Download card</button><button class="btn btn-saffron" type="button" data-jump="order">Order printed card</button><button class="btn btn-ghost" type="button" data-jump="insure">Protect ${nm}</button></div>
       <p class="hint" id="dlNote" style="margin-top:10px"></p>
     </div>
     <div style="display:grid;gap:10px;justify-items:center"><div class="flipper" id="resFlip" style="max-width:460px"><div class="in"><div class="idc-wrap">${cardFront(d,{photo:MEM.photo,pfx:'r'})}</div><div class="idc-wrap b">${cardBack(d)}</div></div></div><button class="fliphint" type="button" id="resFlipBtn">Flip to see the back</button></div>
   </div>
   <div class="blk" id="b-order">${orderHTML()}</div>
   <div class="blk hl" id="b-insure">${insHTML()}</div>
   <div class="blk"><div class="blk-h"><div><span class="eyebrow">Reminders</span><h3>We'll remember ${nm}'s dates</h3></div><span class="hint">Suggested schedule. Your vet can change it.</span></div><div class="rem">${reminders()}</div></div>
   <div class="blk"><div class="blk-h"><div><span class="eyebrow">More for ${nm}</span><h3>Things owners add next</h3></div></div><div class="upsell">
     ${S.neutered==='no'?`<div class="up"><b>Spay / neuter at a partner clinic</b><span>Members get a discount at participating clinics after a 30-day wait.</span><button class="btn btn-ghost btn-sm" type="button" data-toast="We'll WhatsApp you partner clinic slots at launch.">Show me clinics</button></div>`:''}
     <div class="up"><b>WhatsApp vet help</b><span>Photos, videos and voice notes to a vet. Included in both insurance plans.</span><button class="btn btn-ghost btn-sm" type="button" data-jump="insure">See plans</button></div>
     <div class="up"><b>GPS tracker</b><span>4G live location. Included in the Complete plan.</span><button class="btn btn-ghost btn-sm" type="button" data-jump="insure">Add GPS</button></div>
     <div class="up"><b>Another dog or cat?</b><span>Every family member gets their own ID.</span><button class="btn btn-ghost btn-sm" type="button" id="addAnother">Make another ID</button></div>
   </div></div>
  </div>`;
  bindResult();
  if(goal==='insure'){goal='';setTimeout(()=>jump('insure'),400)}
}
function orderHTML(){
  if(orderStep==='done')return `<div class="blk-h"><div><span class="eyebrow">Printed card</span><h3>Pre-order placed</h3></div></div><div class="done-msg">✓ Order ${esc(order.no)} reserved: ${esc(PRODS[order.prod][0])} for ${esc(S.name)}. We'll confirm the price and dispatch on WhatsApp at launch. Pay on delivery.</div>`;
  if(orderStep==='addr')return `<div class="blk-h"><div><span class="eyebrow">Printed card · delivery</span><h3>Where should we send it?</h3></div><span class="chip">${esc(PRODS[order.prod][0])}</span></div>
   <div class="grid2"><div class="fld"><label for="oa1">Name</label><input class="inp" id="oa1" value="${esc(S.oName)}"></div><div class="fld"><label for="oa2">Mobile</label><div class="pfx"><span>+91</span><input class="inp" id="oa2" inputmode="numeric" maxlength="10" value="${esc(S.oMobile)}"></div></div></div>
   <div class="fld"><label for="oa3">Address</label><input class="inp" id="oa3" value="${esc([S.oHouse,S.oLocality].filter(Boolean).join(', '))}"></div>
   <div class="grid3"><div class="fld"><label for="oa4">City</label><input class="inp" id="oa4" value="${esc(S.oCity)}"></div><div class="fld"><label for="oa5">State</label><input class="inp" id="oa5" value="${esc(S.oState)}"></div><div class="fld"><label for="oa6">PIN</label><input class="inp" id="oa6" inputmode="numeric" maxlength="6" value="${esc(S.oPin)}"></div></div>
   <span class="errm" id="oErr"></span>
   <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-ghost" type="button" id="oBack">Back</button><button class="btn btn-saffron" type="button" id="oPlace">Place pre-order · pay on delivery</button></div>`;
  return `<div class="blk-h"><div><span class="eyebrow">Printed card</span><h3>Put ${esc(S.name)}'s ID on the collar</h3></div><span class="hint">Expected ₹200–300 per item. Final price at launch.</span></div>
   <div class="prods">${Object.entries(PRODS).map(([k,[t,s]])=>`<button type="button" class="prod" data-prod="${k}" aria-pressed="${order.prod===k}"><div class="art">${prodArt(k)}</div><b>${t}</b><span>${s}</span></button>`).join('')}</div>
   <div><button class="btn btn-saffron" type="button" id="oNext">Continue to delivery</button></div>`}
function insHTML(){const nm=esc(S.name);
  if(insStep==='done')return `<div class="blk-h"><div><span class="eyebrow">Insurance</span><h3>Quote requested for ${nm}</h3></div></div><div class="done-msg">✓ Request ${esc(ins.no)}: ${ins.plan==='complete'?'Complete':'Essential'} plan, ₹${(+ins.si).toLocaleString('en-IN')} cover. A licensed advisor will WhatsApp your exact premium at launch.</div><p class="hint">The insurer decides cover and price after reviewing the details and photos.</p>`;
  if(insStep==='docs'){const A=['Front','Back','Left','Right','Top'];return `<div class="blk-h"><div><span class="eyebrow">Insurance · step 2 of 2</span><h3>Photos the insurer may ask for</h3></div></div>
   <p class="hint">Some insurers ask for 5 photos of ${nm} with today's newspaper visible. You can also send them later on WhatsApp.</p>
   <div class="angles">${A.map(a=>`<label class="angle ${MEM.angles[a]?'done':''}">${MEM.angles[a]?`<img src="${MEM.angles[a]}" alt="${a}">`:`+<br>${a}`}<input type="file" accept="image/*" data-angle="${a}" aria-label="${a} photo"></label>`).join('')}</div>
   ${S.kci==='yes'?`<div class="note blue">KCI pedigree on file (${esc(S.kciNo)}). Useful for higher cover amounts.</div>`:''}
   ${!S.kycType?`<div class="note">Owner ID proof is needed before a policy is issued. You can add it in the ID details.</div>`:''}
   <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-ghost" type="button" id="iBack">Back</button><button class="btn btn-blue" type="button" id="iSend">Request my quote</button></div>`}
  return `<div class="blk-h"><div><span class="eyebrow">Insurance · step 1 of 2</span><h3>Protect ${nm}</h3></div><span class="hint">Breed, age and vet are already filled in from the ID.</span></div>
   <div class="choices" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
    <button type="button" class="choice" data-plan="essential" aria-pressed="${ins.plan==='essential'}">Essential<small>Insurance, 10 WhatsApp vet consults a year, steel QR tag, partner clinic savings</small></button>
    <button type="button" class="choice" data-plan="complete" aria-pressed="${ins.plan==='complete'}">Complete · with GPS<small>Everything in Essential plus a 4G GPS tracker and first year of tracking</small></button></div>
   <div class="fld"><span class="lb">Cover amount</span><div class="pills">${['25000','50000','100000'].map(v=>`<button type="button" class="pill" data-si="${v}" aria-pressed="${ins.si===v}">₹${(+v).toLocaleString('en-IN')}</button>`).join('')}</div><span class="hint">Options depend on the insurer and ${nm}'s breed and age.</span></div>
   <div class="fld"><span class="lb">Has ${nm} been ill or injured in the last 12 months?</span><div class="pills">${['No','Yes'].map(v=>`<button type="button" class="pill" data-ill="${v}" aria-pressed="${ins.ill===v}">${v}</button>`).join('')}</div></div>
   <div class="fld"><span class="lb">Any ongoing condition?</span><div class="pills">${['No','Yes'].map(v=>`<button type="button" class="pill" data-pre="${v}" aria-pressed="${ins.pre===v}">${v}</button>`).join('')}</div><span class="hint">Pre-existing conditions are usually not covered. Declaring them avoids claim problems later.</span></div>
   <span class="errm" id="iErr"></span>
   <div><button class="btn btn-blue" type="button" id="iNext">Continue</button></div>`}
function jump(k){const el=$('#b-'+k);el&&el.scrollIntoView({behavior:reduce?'auto':'smooth',block:'start'})}
function bindResult(){
  $('#resFlip').onclick=()=>$('#resFlip').classList.toggle('flipped');$('#resFlipBtn').onclick=()=>$('#resFlip').classList.toggle('flipped');
  Q.querySelectorAll('[data-jump]').forEach(b=>b.onclick=()=>jump(b.dataset.jump));
  Q.querySelectorAll('[data-toast]').forEach(b=>b.onclick=()=>toast(b.dataset.toast));
  $('#addAnother').onclick=()=>{const keep=ME.user?{oName:S.oName,oMobile:S.oMobile,oEmail:S.oEmail,oHouse:S.oHouse,oLocality:S.oLocality,oCity:S.oCity,oState:S.oState,oPin:S.oPin,eName:S.eName,eMobile:S.eMobile}:{};S={...blank(),...keep};MEM.photo='';MEM.docs=[];MEM.kycFiles=[];MEM.angles={};orderStep='';insStep='';step=0;view='steps';save();Q.removeAttribute('style');render()};
  setupDownload();bindOrder();bindIns();
}
function bindOrder(){const b=$('#b-order');
  b.querySelectorAll('[data-prod]').forEach(x=>x.onclick=()=>{order.prod=x.dataset.prod;b.innerHTML=orderHTML();bindOrder()});
  const n=$('#oNext');if(n)n.onclick=()=>{orderStep='addr';b.innerHTML=orderHTML();bindOrder()};
  const bk=$('#oBack');if(bk)bk.onclick=()=>{orderStep='';b.innerHTML=orderHTML();bindOrder()};
  const pl=$('#oPlace');if(pl)pl.onclick=()=>{const m=$('#oa2').value,p=$('#oa6').value,a=$('#oa3').value;
    if(!/^[6-9]\d{9}$/.test(m)){$('#oErr').textContent='Enter a valid 10-digit mobile.';return}if(!/^\d{6}$/.test(p)){$('#oErr').textContent='PIN codes are 6 digits.';return}if(!a.trim()){$('#oErr').textContent='Add a delivery address.';return}
    pl.disabled=true;pl.textContent='Placing…';
    const local=()=>{order.no='ORD-'+(hash(S.idNo+Date.now())%90000+10000);orderStep='done';b.innerHTML=orderHTML();confetti(60)};
    if(!S.saved){local();return}
    api('order.php',{id_no:S.idNo,product:order.prod,name:$('#oa1').value,mobile:m,address:a,city:$('#oa4').value,state:$('#oa5').value,pin:p})
      .then(j=>{order.no=j.order_no;orderStep='done';b.innerHTML=orderHTML();confetti(60)})
      .catch(e=>{pl.disabled=false;pl.textContent='Place pre-order · pay on delivery';$('#oErr').textContent=e.error||'Could not place the order. Please try again.'})}}
function bindIns(){const b=$('#b-insure');
  b.querySelectorAll('[data-plan]').forEach(x=>x.onclick=()=>{ins.plan=x.dataset.plan;b.innerHTML=insHTML();bindIns()});
  ['si','ill','pre'].forEach(k=>b.querySelectorAll(`[data-${k}]`).forEach(x=>x.onclick=()=>{ins[k]=x.dataset[k];b.innerHTML=insHTML();bindIns()}));
  const n=$('#iNext');if(n)n.onclick=()=>{if(!ins.ill||!ins.pre){$('#iErr').textContent='Please answer both health questions.';return}insStep='docs';b.innerHTML=insHTML();bindIns()};
  const bk=$('#iBack');if(bk)bk.onclick=()=>{insStep='';b.innerHTML=insHTML();bindIns()};
  b.querySelectorAll('[data-angle]').forEach(i=>i.addEventListener('change',()=>readImg(i.files[0],u=>{MEM.angles[i.dataset.angle]=u;b.innerHTML=insHTML();bindIns()})));
  const s=$('#iSend');if(s)s.onclick=()=>{
    const done=no=>{ins.no=no;insStep='done';b.innerHTML=insHTML();confetti(60)};
    if(!S.saved){done('Q-'+(hash(S.idNo+ins.plan+Date.now())%90000+10000));return}
    s.disabled=true;s.textContent='Sending…';
    const fd=new FormData();fd.append('data',JSON.stringify({id_no:S.idNo,plan:ins.plan,si:ins.si,ill:ins.ill,pre:ins.pre}));
    Object.entries(MEM.angles).forEach(([k,u])=>fd.append('angle_'+k,dataURLtoBlob(u),k+'.jpg'));
    api('quote.php',fd).then(j=>done(j.quote_no)).catch(e=>{s.disabled=false;s.textContent='Request my quote';toast(e.error||'Could not send. Please try again.')})}}

/* ---- Download card as PNG (front + back) ---- */
let dl=null;
(async()=>{try{dl=window.claude&&window.claude.use?await window.claude.use('downloads'):null}catch(e){dl=null}})();
function setupDownload(){const btn=$('#dlBtn'),note=$('#dlNote');
  btn.onclick=async()=>{
    btn.disabled=true;btn.textContent='Preparing…';
    try{const blob=await renderPNG(),fname=`${(S.name||'pet').replace(/[^\w-]+/g,'_')}_ID_${S.idNo}.png`;
      if(dl){await dl.save({filename:fname,data:blob})}else{const u=URL.createObjectURL(blob),a=document.createElement('a');a.href=u;a.download=fname;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(u),4000)}note.textContent='Saved. Print it on card stock, or keep it on your phone.';toast('Card saved ✓')}
    catch(e){note.textContent=e&&e.code==='declined'?'Download cancelled.':'Couldn\'t save the file here. Try again, or order a printed card.'}
    btn.disabled=false;btn.textContent='⬇ Download card'}}
function loadImg(src){return new Promise((res,rej)=>{const i=new Image();i.onload=()=>res(i);i.onerror=rej;i.src=src})}
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
async function renderPNG(){
  try{await document.fonts.ready}catch(e){}
  const W=1012,H=638,G=40,cv=document.createElement('canvas');cv.width=W;cv.height=H*2+G;const c=cv.getContext('2d');
  c.fillStyle='#FFFFFF';c.fillRect(0,0,cv.width,cv.height);
  const F='"Plus Jakarta Sans", Arial, sans-serif',M='"JetBrains Mono", monospace';
  const bg=(y)=>{c.save();rr(c,0,y,W,H,56);c.clip();const g=c.createRadialGradient(0,y,10,0,y,W*1.3);g.addColorStop(0,'#2B5BFF');g.addColorStop(.45,'#1739B0');g.addColorStop(1,'#0D1B3E');c.fillStyle=g;c.fillRect(0,y,W,H);
    c.strokeStyle='rgba(255,255,255,.05)';c.lineWidth=2;for(let r=40;r<1300;r+=18){c.beginPath();c.arc(W*1.15,y+H*1.2,r,0,Math.PI*2);c.stroke()}
    const sg=c.createLinearGradient(0,0,W,0);sg.addColorStop(0,'#FF7A2F');sg.addColorStop(1,'#FFB37E');c.fillStyle=sg;c.fillRect(0,y+H-22,W,22);c.restore()};
  const lbl=(t,x,y)=>{c.fillStyle='rgba(255,255,255,.65)';c.font=`700 19px ${F}`;c.fillText(t.toUpperCase().split('').join(String.fromCharCode(8202)),x,y)};
  const val=(t,x,y,max,size=31)=>{c.fillStyle='#fff';c.font=`700 ${size}px ${F}`;let s=String(t);while(c.measureText(s).width>max&&s.length>3)s=s.slice(0,-2)+'…';c.fillText(s,x,y)};
  const header=(y)=>{c.fillStyle='#fff';rr(c,56,y+44,60,60,16);c.fill();c.fillStyle='#2457F5';c.font=`800 34px ${F}`;c.fillText('🐾',63,y+88);c.fillStyle='#fff';c.font=`800 33px ${F}`;c.fillText('IndiaPetInsurance.com',132,y+86);
    const k=S.species==='cat'?'CAT ID':'DOG ID';c.font=`800 22px ${F}`;const kw=c.measureText(k).width+40;c.fillStyle='rgba(255,255,255,.14)';rr(c,W-56-kw,y+52,kw,44,22);c.fill();c.fillStyle='#fff';c.fillText(k,W-56-kw+20,y+82)};
  // FRONT
  bg(0);header(0);
  c.save();rr(c,56,150,272,334,34);c.clip();const pg=c.createLinearGradient(56,150,328,484);pg.addColorStop(0,'#FFE7D3');pg.addColorStop(1,'#FFC9A3');c.fillStyle=pg;c.fillRect(56,150,272,334);
  try{if(MEM.photo){const im=await loadImg(MEM.photo);const s=Math.max(272/im.width,334/im.height);c.drawImage(im,56+(272-im.width*s)/2,150+(334-im.height*s)/2,im.width*s,im.height*s)}else{const svg=portraitSVG(sp(),S.breed,'dl').replace('<svg ','<svg xmlns="http://www.w3.org/2000/svg" width="360" height="333" ');const im=await loadImg('data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg));c.drawImage(im,56-44,150+18,360,333)}}catch(e){}
  c.restore();c.strokeStyle='rgba(255,255,255,.9)';c.lineWidth=6;rr(c,56,150,272,334,34);c.stroke();
  const X=374;c.fillStyle='#fff';c.font=`800 74px ${F}`;let n=S.name;while(c.measureText(n).width>W-X-56&&n.length>3)n=n.slice(0,-2)+'…';c.fillText(n,X,222);
  lbl('Breed',X,268);val(S.breed,X,304,W-X-56);
  lbl('Sex',X,350);val((S.gender==='m'?'Male':S.gender==='f'?'Female':'—')+(S.neutered==='yes'?(S.gender==='f'?' · Spayed':' · Neutered'):''),X,386,280);
  lbl('Age',X+300,350);val(ageText(S)||'—',X+300,386,280);
  lbl('ID No.',X,432);c.fillStyle='#fff';c.font=`700 31px ${M}`;c.fillText(S.idNo,X,468);
  // badges
  let bx=56;const bd=[['Vaccinated',!!(S.rabies||S.records==='yes')],['Licensed',S.lic==='yes'],['Chip',S.chip==='yes']];if(S.kci==='yes')bd.splice(2,0,['KCI',true]);
  c.font=`800 20px ${F}`;bd.forEach(([t,on])=>{const s=(on?'✓ ':'○ ')+t,w=c.measureText(s).width+36;c.fillStyle=on?'#fff':'rgba(255,255,255,.14)';rr(c,bx,530,w,44,22);c.fill();c.fillStyle=on?'#0E9F58':'#fff';c.fillText(s,bx+18,559);bx+=w+12});
  // QR
  c.fillStyle='#fff';rr(c,W-56-172,H-60-172,172,172,20);c.fill();const m=qrFor(S.idNo),cs=148/m.length;c.fillStyle='#0D1B3E';m.forEach((row,y)=>row.forEach((v,x)=>{if(v)c.fillRect(W-56-172+12+x*cs,H-60-172+12+y*cs,cs+.3,cs+.3)}));
  // BACK
  const y0=H+G;bg(y0);
  c.fillStyle='#fff';c.font=`800 38px ${F}`;c.fillText('If found, please scan the QR',56,y0+90);c.fillText(`or call ${S.oName?S.oName.split(' ')[0]:'my family'}.`,56,y0+136);
  const ph=S.pubPhone&&S.oMobile?`+91 ${S.oMobile.slice(0,5)} ${S.oMobile.slice(5)}`:'Hidden by owner';
  const ad=(S.pubAddr==='exact'?[S.oHouse,S.oLocality,S.oCity]:[S.oLocality,S.oCity]).filter(Boolean).join(', ')||'—';
  const vet=S.pubVet?(S.doctor?'Dr. '+S.doctor.replace(/^dr\.?\s*/i,''):(S.clinic||'—')):'Hidden';
  lbl('Owner phone',56,y0+200);val(ph,56,y0+238,420);lbl('Area',530,y0+200);val(ad,530,y0+238,426);
  lbl('Primary vet',56,y0+296);val(vet,56,y0+334,420);lbl('Clinic',530,y0+296);val(S.pubVet?(S.clinic||'—'):'Hidden',530,y0+334,426);
  lbl('Allergies / notes',56,y0+392);val(S.pubAllergy?(S.conditions||'None declared'):'Hidden',56,y0+430,900);
  c.fillStyle='rgba(255,255,255,.55)';c.font=`500 22px ${M}`;c.fillText((`IPI<${S.idNo.replace(/-/g,'<')}<<${S.name.toUpperCase().replace(/[^A-Z]/g,'<')}`+'<'.repeat(40)).slice(0,52),56,y0+H-52);
  return await new Promise(r=>cv.toBlob(r,'image/png'));
}

/* ---- toast + confetti ---- */
function toast(t){const o=$('.toast');o&&o.remove();const d=document.createElement('div');d.className='toast';d.textContent=t;document.body.appendChild(d);setTimeout(()=>d.remove(),3200)}
function confetti(n=160){if(reduce)return;const cv=$('#confetti');cv.hidden=false;const c=cv.getContext('2d');cv.width=innerWidth;cv.height=innerHeight;
  const cols=['#2457F5','#FF7A2F','#0E9F58','#FFC94A','#FFFFFF'];const P=Array.from({length:n},()=>({x:innerWidth/2+(Math.random()-.5)*200,y:innerHeight*.35,vx:(Math.random()-.5)*16,vy:-Math.random()*14-4,r:Math.random()*6+4,c:cols[Math.random()*5|0],a:Math.random()*6,va:(Math.random()-.5)*.4}));let t=0;
  (function f(){c.clearRect(0,0,cv.width,cv.height);P.forEach(p=>{p.vy+=.4;p.x+=p.vx;p.y+=p.vy;p.vx*=.99;p.a+=p.va;c.save();c.translate(p.x,p.y);c.rotate(p.a);c.fillStyle=p.c;c.fillRect(-p.r,-p.r/2,p.r*2,p.r);c.restore()});if(++t<150)requestAnimationFrame(f);else{c.clearRect(0,0,cv.width,cv.height);cv.hidden=true}})()}

/* ================= WALKING PETS ================= */
$('#duoSvg').innerHTML=`<g>${dogArt('d')}</g><g transform="translate(318 20) scale(.92)">${catArt('c')}</g>`;
const duo=$('#duo'),flipEl=duo.querySelector('.flip'),lane=$('#lane'),bubble=$('#bubble'),spot=$('#spot'),hero=$('#top');
const legs=[...duo.querySelectorAll('[data-leg]')];
if(reduce)$$('svg').forEach(s=>s.pauseAnimations&&s.pauseAnimations());
const secs=$$('section[data-mark]');
$('#marks').innerHTML=secs.map((s,i)=>`<button class="lmk" type="button" data-i="${i}" aria-label="Go to ${s.dataset.mark}"><span>${s.dataset.mark}</span><i></i></button>`).join('');
const mks=$$('.lmk');mks.forEach(m=>m.onclick=()=>secs[+m.dataset.i].scrollIntoView({behavior:reduce?'auto':'smooth'}));
const NW=560,NH=280,FEET=262/280;let G={};
function layout(){const r=spot.getBoundingClientRect();const lh=parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--lane'))||96;
  G={sx:r.left,sy:r.top+scrollY,sw:r.width,heroH:hero.offsetHeight,maxS:Math.max(1,document.documentElement.scrollHeight-innerHeight),tops:secs.map(s=>s.getBoundingClientRect().top+scrollY),lh,safe:Math.max(0,lane.offsetHeight-lh)};G.s1=G.heroH*.38;G.s2=G.heroH*.66}
const lerp=(a,b,t)=>a+(b-a)*t,clamp=(v,a,b)=>Math.max(a,Math.min(b,v)),ease=t=>t<.5?2*t*t:1-Math.pow(-2*t+2,2)/2;
let lastX=null,facing=1,phase=0,amp=0,lastSay='',sayLock=0;
function frame(){
  if(!wiz.hidden){requestAnimationFrame(frame);return}
  const s=scrollY,vw=document.documentElement.clientWidth,vh=innerHeight,lh=G.lh;
  const k0=G.sw/NW,x0=G.sx,y0=G.sy-s,k1=(lh-14)/NH,w1=NW*k1,y1=vh-G.safe-14-NH*k1*FEET,gut=16,xS=gut,xE=Math.max(gut,vw-w1-gut),xD=clamp(x0+G.sw/2-w1/2,gut,xE);
  let x,y,k,t1;
  if(s<=G.s1){const t=ease(clamp(s/G.s1,0,1));x=lerp(x0,xD,t);y=lerp(y0,y1,t);k=lerp(k0,k1,t);t1=t}
  else if(s<=G.s2){const t=ease((s-G.s1)/(G.s2-G.s1));x=lerp(xD,xS,t);y=y1;k=k1;t1=1}
  else{const p=clamp((s-G.s2)/(G.maxS-G.s2),0,1);x=lerp(xS,xE,p);y=y1;k=k1;t1=1}
  lane.style.transform=`translateY(${(1-t1)*105}%)`;
  const dx=lastX===null?0:x-lastX;lastX=x;if(Math.abs(dx)>.4&&t1>.6)facing=dx>0?1:-1;if(t1<.6)facing=1;
  if(!reduce&&t1>.5){phase+=Math.abs(dx)*.11;amp=Math.min(1,amp+Math.abs(dx)*.08)}amp*=.9;
  const ang=Math.sin(phase)*26*amp,bob=-Math.abs(Math.sin(phase))*10*amp;
  legs.forEach(l=>l.setAttribute('transform',`rotate(${l.dataset.leg==='a'?ang:-ang})`));
  duo.style.transform=`translate(${x}px,${y+bob*k}px) scale(${k})`;flipEl.style.transform=`scaleX(${facing})`;duo.style.opacity=1;
  const bw=bubble.offsetWidth;let bx=facing>0?x+w1+10:x-bw-10;if(bx+bw>vw-gut)bx=x-bw-10;if(bx<gut)bx=x+w1+10;bubble.style.left=bx+'px';
  const span=xE-xS;mks.forEach((m,i)=>{const st=G.tops[i]-vh*.55,p=clamp((st-G.s2)/(G.maxS-G.s2),0,1),mx=xS+p*span+w1*.75;m.style.left=mx+'px';m.classList.toggle('on',s>=st);m.classList.toggle('near',mx>x-40&&mx<x+w1+40)});
  if(Date.now()>sayLock){let txt='';if(s>G.s2*.9)for(let i=0;i<secs.length;i++){if(G.tops[i]<=s+vh*.55)txt=secs[i].dataset.say}
    if(txt!==lastSay){lastSay=txt;bubble.textContent=txt;bubble.classList.toggle('show',!!txt);bubble.classList.remove('pop');void bubble.offsetWidth;if(txt)bubble.classList.add('pop')}}
  requestAnimationFrame(frame)}
duo.addEventListener('click',()=>{['#sayDog','#sayCat'].forEach(q=>{const e=$(q);e.classList.remove('pop');void e.offsetWidth;e.classList.add('pop')});if(scrollY>G.s2*.9){bubble.textContent='Woof! Meow! Make our IDs!';bubble.classList.add('show');lastSay='__';sayLock=Date.now()+1600}});
duo.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();duo.click()}});
layout();requestAnimationFrame(frame);addEventListener('resize',layout);addEventListener('load',layout);
if(document.fonts&&document.fonts.ready)document.fonts.ready.then(layout);new ResizeObserver(layout).observe(document.body);
// returning visitor nudge
load();if(S.idNo){$$('[data-start]').forEach(b=>{if(b.closest('nav'))b.textContent=`${S.name}'s ID`})}
refreshMe();
function hashOpen(){if(location.hash==='#make-id'){S.idNo&&S.saved&&(S={...blank()},save());open('dog');history.replaceState(null,'',location.pathname)}}
hashOpen();addEventListener('hashchange',hashOpen);
// Older browsers without container query units: size ID cards from their width
if(!(window.CSS&&CSS.supports&&CSS.supports('width','1cqw'))&&window.ResizeObserver){const ro=new ResizeObserver(es=>es.forEach(en=>en.target.style.setProperty('--cq',(en.contentRect.width/100)+'px')));const watch=()=>$$('.idc-wrap').forEach(w=>{if(!w.__ro){w.__ro=1;ro.observe(w)}});watch();new MutationObserver(watch).observe(document.body,{childList:true,subtree:true})}
})();
