<template>
  <div>
    <div class="canvas" v-if="canvas">
      <div class="content" v-html="canvasObsah"></div>
      <infinite-scroll @overflow="onOverflow" v-if="overflow">
        <div v-for="(item, i) of infiniteScrollFeed" class="infiniteScroll" v-html="item" :key="i" />
      </infinite-scroll>
      <div class="errorMessage" @click="errorMessage = null" v-if="errorMessage">{{ errorMessage }}</div>
    </div>
    <form id="uploadForm">
      <input type="file" accept="image/*" ref="uploadImage" @change="uploadFile" />
    </form>

    <div :class="['commandline', viceRadek ? 'viceRadek' : '']" ref="commandline">
      <input 
        type="text" 
        class="input"
        :placeholder="zprava" 
        @keyup.shift.enter="prepniNaViceRadek"
        @keyup.enter="provedPrikaz"
        @keyup.up="outputNahoru"
        @keyup.down="outputDolu"
        @keyup.esc="command = ''"
        v-if="!viceRadek"
        v-model="command"
        ref="singleInput"
        list="oblibene-prikazy"
      />
      <datalist id="oblibene-prikazy" v-if="autocomplete">
        <option :value="prikaz" v-for="(prikaz, i) of this.data.fav?.list" :key="i" />
      </datalist>
      <div v-if="viceRadek" class="viceRadek-kontejner">
        <textarea 
          :class="['input', small ? 'small' : '']" 
          :placeholder="zprava" 
          ref="input"  
          id="input" 
          @keyup.ctrl.enter="provedPrikaz"
          @keyup.esc="viceRadek = false"
          v-model="command" 
        ></textarea>
      </div>
    </div>
    <div class="outputFrame" ref="outputFrame">
      <div :class="['output', line[1], index === outputIndex ? 'oznacenyRadek' : '']" v-for="(line, index) of filteredOutput" ref="output">
        <pre class="pre-input">{{line[2]}}</pre>
        <pre class="pre-output" v-if="!line[3]" v-text="line[0]" />
        <div class="pre-output" v-if="line[3]" v-text="line[0]" />
      </div>
    </div>
    <div :class="['menu', filteredOutput.length > 0 ? filteredOutput[0][1] : '']">
      <button @click="viceRadek = !viceRadek">*</button>
      <button @click="outputNahoru">&uarr;</button>
      <button @click="outputDolu">&darr;</button>
      <input type="checkbox" v-model="autocomplete" />
      <input type="checkbox" v-model="continual" />
      <input type="checkbox" v-model="small" />
      <button @click="provedPrikaz">&crarr;</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import yaml from 'js-yaml'
import _ from 'lodash'
import * as vue from 'vue'
import InfiniteScroll from './InfiniteScroll.vue'
import { marked } from 'marked'
const CryptoJS = require("crypto-js");
import { renderToString } from 'vue/server-renderer';

const Authorization = '4Dh0io5Pks0ZP6mKPW3dPMoazd3yUdt0UwqtK5IvnkzMV7OwoP'
const commands = ["dir", "cd"]

export default {
  data() {
    return {
      token: "",
      data: {},
      dataBackup: {},
      output: [],
      outputIndex: -1,
      viceRadek: false,
      stisknutyShift: false,
      command: "",
      heslo: null,
      ulozenePrikazy: null,
      hesla: {},
      fPrefix: "",
      vue,
      showMenu: false,
      menuPrefix: [],
      renderToString,
      imageFile: null,
      imageFilePath: '',
      floatMenu: false,
      canvas: null,
      canvasRefresh: 0,
      errorMessage: null,
      infiniteScrollFeed: [],
      overflow: "",
      autocomplete: false,
      continual: false,
      small: false,
    }
  },
  components: { InfiniteScroll },
  computed: {
    zprava() {
      return this.fPrefix + "..."
    },
    filteredOutput() {
      return this.output.slice(0, this.outputIndex < 20 ? 20 : this.outputIndex)
    },
    oznacenyRadek() {
      return this.filteredOutput.length + this.outputIndex
    },
    commandLineHeight() {
      this.viceRadek
      return this.$refs.commandline.clientHeight + "px"
    },
    yaml() {
      return yaml
    },
    canvasObsah() {
      if (this.canvas) {
        try {
          this.canvasRefresh
          const result = this.f(this.canvas)
          return result
        } catch (e) {
          console.log(e)
          this.errorMessage = e.toString()
          return ''
        }
      } else {
        return ''
      }
    }
  },
  methods: {
    async provedPrikaz() {
      if (this.stisknutyShift) {
        this.stisknutyShift = false
        return;
      }

      let value = this.command
      if (!this.continual) {
        this.command = ""
      }

      let finalValue = value

      if (value.match(new RegExp(`^(${commands.join("|")})`))) {
        const params = value.split(" ")
        const command = params.shift()
        value = `this.${command}("${params}")`
      }

      let match

      match = value.match(/^#(\S+)(\s+(.*))?/s)
      if (match) {
        value = `this.f("${match[1]}", ${match[3]})`
      }
      
      try {
        const result = await eval(value)
        this.log(typeof result !== "string" ? yaml.dump(result) : result, 'normal', finalValue, typeof result === "string")
      } catch(ex) {
        console.error(ex);
        this.log(ex, 'error', finalValue)
      }
    },
    outputNahoru() {
      if (this.outputIndex > -1) {
        this.outputIndex--
      }
    },
    outputDolu() {
      if (this.outputIndex < this.output.length) {
        this.outputIndex++
      }
    },
    dir() {
      let slozka
      if (this.fPrefix.length) {
        slozka = eval(`this.data.f.${this.fPrefix}`)
      } else {
        slozka = this.data.f
      }
      return Object.entries(slozka).map(([key, value]) => {
        const typ = typeof value
        if (typ === "object") {
          return `${key}: <DIR>`
        } else if (typ === "string") {
          const params = value.split("=>")[0].trim()
          return `${key}: ${params}`
        } else {
          return `${key}: ${typ}`
        }
      })
    },
    cd(fPrefix = "") {
      if (fPrefix.length === 0) {
        this.fPrefix = ""
      } else {
        const slozka = eval(`this.data.f.${fPrefix}`)
        if (typeof slozka === "object") {
          this.fPrefix = fPrefix
        } else {
          throw "Složka neexistuje"
        }
      }
      localStorage.setItem("dir", fPrefix)
    },
    f(path, ...params) {
      let code
      try {
        code = eval(`this.data.${path}`)
      } catch (e) {
        return
      }
      if (!code) {
        return
      } 
      const fce = eval(code);
      if (fce) {
        return fce(...params)
      }
    },
    prepniNaViceRadek() {
      this.viceRadek = true
      this.stisknutyShift = true
    },
    posledniPrikaz() {
      if (this.command.length === 0 && this.output.length > 0) {
        this.command = this.output[this.output.length - 1][2]
      }
    },
    predchoziPrikaz(index) {
      this.command = this.output[index][2]
      this.$refs.singleInput.focus()
    },
    async baliky() {
      return await axios.get(API_URL + '/balik', {
        headers: { Authorization }
      }).then(res => res.data)
    },
    otevri(nazev, heslo) {
      return axios.get(API_URL + '/balik/' + nazev, { headers: { Authorization }, params: { ts: new Date().getTime() } }).then(res => {
        try {
          const dataJSON = CryptoJS.AES.decrypt(res.data, heslo).toString(CryptoJS.enc.Utf8)
          const data = JSON.parse(dataJSON)
          this.dataBackup[nazev] = data
          this.data[nazev] = data
          this.hesla[nazev] = heslo
          localStorage.setItem('hesla', JSON.stringify(this.hesla))
          return Promise.resolve(data)
        } catch (e) {
          console.error(e)
          return Promise.reject("Balík nebyl načten, zkontrolujte, zda neselhala komunikace se serverem či zda jste nezadali špatné heslo.")
        }
      })
    },
    uloz(nazev) {
      if (!this.data[nazev]) {
        return "Neplatný název balíku"
      }
      const heslo = this.hesla[nazev]
      const record = CryptoJS.AES.encrypt(JSON.stringify(this.data[nazev]), heslo).toString()
      axios.put(API_URL + '/balik', { nazev, data: record }, {
        headers: { Authorization }
      }).then(() => {
        return nazev + " byl uložen"
      })
    },
    export(nazev, data) {
      axios.post(API_URL + '/export/' + nazev, data, {
        headers: { Authorization }
      }).then(() => {
        return nazev + " byl exportován"
      })
    },
    zavri(data) {
      this.uloz(data)
      const [nazev] = data.split(/\s+/)
      this.data[nazev] = null
      return nazev + " byl uložen a uzavřen"
    },
    log(vystup, status, prikaz, wrap = true) {
      this.output.unshift([vystup, status, prikaz, wrap])
      if (!this.continual) {
        this.viceRadek = false
      }
    },
    menuClick(nazev, typ, vratit = true) {
      if (typ === "object") {
        this.menuPrefix.push(nazev)
      } else {
        if (nazev === "<<<") {
          this.menuPrefix.pop()
        } else {
          if (this.menuPrefix.length > 0) {
            this.command = `this.f(".${this.menuPrefix.join(".")}.${nazev}")`
          } else {
            this.command = `this.f(".${nazev}")`
          }
          if (vratit) {
            this.menuPrefix = []
          }
          this.showMenu = false
          this.provedPrikaz()
        }
      }
    },
    upload() {
      this.$refs.uploadImage.click()
    },
    async uploadFile() {
      const formData = new FormData();
      formData.append("image", this.$refs.uploadImage.files[0]);
      return await axios.post(API_URL + "/image", formData, {
        headers: { Authorization }
      }).then(res => {
        this.$refs.uploadImage.value = ""
        this.f("upload", res.data)
        this.log(res.data, "normal", "")
      })
    },
    async loadFileFromUrl(url, path) {
      return await axios.post(API_URL + '/url-image', { url, path }, {
        headers: { Authorization }
      }).then(res => res.data)
    },
    md(input) {
      return marked.parse(input)
    },
    onOverflow() {
      this.f(this.overflow)
    },

    deleteImage(path) {
      axios.delete(API_URL + "/image/" + path, {
        headers: { Authorization }
      })
    }
  },
  watch: {
    data: {
      handler() {
        for (const key of Object.keys(this.data)) {
          if (!_.isEqual(this.data[key], this.dataBackup[key])) {
            this.uloz(key)
          }
        }
        this.dataBackup = _.cloneDeep(this.data)
      },
      deep: true
    },
    hesla: {
      handler() {
        localStorage.setItem('hesla', JSON.stringify(this.hesla))
      },
      deep: true
    },
    output: {
      handler() {
        localStorage.setItem('output', JSON.stringify(this.output))
        this.$nextTick(() => {
           this.$refs.outputFrame.scrollTop = 0
        })
      },
      deep: true
    },
    viceRadek(value) {
      localStorage.setItem("viceRadek", JSON.stringify(value))
      this.$nextTick(() => {
        if (value) {
          this.$refs.input.focus();
        } else {
          this.$refs.singleInput.focus();
        }
      })
    },
    autocomplete(value) {
      localStorage.setItem("autocomplete", JSON.stringify(value))
    },
    continual(value) {
      localStorage.setItem("continual", JSON.stringify(value))
    },
    small(value) {
      localStorage.setItem("small", JSON.stringify(value))
    },
    outputIndex(value) {
      if (value === -1) {
        const command = localStorage.getItem("command") || ""
        this.command = command
      }
      if (this.filteredOutput[value]) {
        this.command = this.filteredOutput[value][2]
      }
      this.$nextTick(() => {
        if (this.$refs.output[value]) {
          this.$refs.outputFrame.scrollTop = this.$refs.output[value].offsetTop
        }
      })
    },
    command(value) {
      if (this.filteredOutput[this.outputIndex] && this.filteredOutput[this.outputIndex][2] !== value) {
        this.outputIndex = -1
        this.$refs.outputFrame.scrollTop = 0
      }
      if (this.outputIndex === -1) {
        localStorage.setItem("command", value)
      }
    },
    canvasObsah() {
      this.$nextTick(() => {
        for (const el of document.querySelectorAll(".canvas [data-click]")) {
          el.addEventListener('click', async (e) => {
            e.preventDefault()
            e.stopPropagation()
            const click = el.attributes['data-click'].value
            const values = []
            if (el.attributes['data-params']) {
              for (const param of el.attributes['data-params'].value.split(",")) {
                values.push(document.querySelector(`[name="${param}"]`).value)
              }
            }
            if (el.attributes['data-refresh']) {
              const balik = el.attributes['data-refresh'].value
              await this.otevri(balik, this.hesla[balik])
            } 
            try {
              this.canvasRefresh++
              this.f(click, ...values)
            } catch (e) {
              this.errorMessage = e.toString()
            }
          })
        }

        for (const el of document.querySelectorAll(".canvas [data-change]")) {
          el.addEventListener('change', async e => {
            if (el.attributes['data-refresh']) {
              const balik = el.attributes['data-refresh'].value
              await this.otevri(balik, this.hesla[balik])
            } 
            const item = el.attributes['data-change'].value
            eval(`${item} = \`${el.value}\``)
          })
        }
      })
    },
    infiniteScrollFeed: {
      handler() {
        this.canvasRefresh++
      },
      deep: true
    }
  },
  mounted() {
    this.fPrefix = localStorage.getItem("dir") || ""
    if (location.hash) {
      this.canvas = location.hash.replace("#", "") + ".canvas"
    }
    const hesla = localStorage.getItem("hesla")
    if (hesla) {
      this.hesla = JSON.parse(hesla)
    }
    for (const [balik, heslo] of Object.entries(this.hesla)) {
      this.otevri(balik, heslo)
    }
    const output = localStorage.getItem("output")
    if (output) {
      this.output = JSON.parse(output)
    }
    this.viceRadek = JSON.parse(localStorage.getItem("viceRadek") || "false")
    this.autocomplete = JSON.parse(localStorage.getItem("autocomplete") || "false")
    this.continual = JSON.parse(localStorage.getItem("continual") || "false")
    this.small = JSON.parse(localStorage.getItem("small") || "false")
    this.command = localStorage.getItem("command") || ""
    this.$refs.singleInput.focus()
  },
}
</script>

<style>
body {
  margin: 0;
  background-color: black;
}
body, td {
  font-family: Georgia, 'Times New Roman', Times, serif;
}
label[for=input] {
  width: 100%;
}
input.input, textarea.input {
  width: calc(100% - 6px);
  background-color: black;
  color: #0C0;
  font-weight: bold;
  border: 1px solid #444;
  outline-width: 0;
  display: inline-block;
  font-size: 14pt;
}

textarea.input {
  height: calc(100% - 31px);
}
textarea.input.small {
  font-size: 6pt;
}
input.input::placeholder, textarea.input::placeholder {
  color: #050;
}

.commandline {
  position: fixed;
  top: 0px;
  width: calc(100% - 30px);
}
.menu {
  position: fixed;
  right: 0px;
  top: 0px;
  text-align: right;
  display: flex;
  flex-direction: column;
}
.menu > button, .menu input[type=checkbox] {
  width: 30px;
  height: 26px;
  background-color: #0C0;
  border: 1px solid #444;
  margin: 0;
}
.menu.error > button {
  background-color: #F00;
}
.menu > ul {
  margin: 0px;
  margin-right: 25px;
}
.menu > ul > li {
  padding: 5px;
  color: #0C0;
  border: 1px solid #444;
  list-style-type: none;
  text-align: left;
  background-color: black;
}
.menu > ul > li.object {
  color: #444;
}

.commandline.viceRadek {
  height: 100%;
}

div.outputFrame {
  position: fixed;
  width: 100%;
  top: v-bind(commandLineHeight);
  left: 0px;
  height: calc(100% - v-bind(commandLineHeight));
  overflow-y: auto;
}
div.output {
  padding-bottom: 5px;
  border: 1px dashed black;
  width: calc(100% - 5px);
  overflow-x: auto;
}
div.output.oznacenyRadek {
  background-color: #005;
}
div.output.normal {
  border-bottom-color: #0C0;
}
div.output.error {
  border-bottom-color: #C00;
}
div.output.normal {
  color: #0C0;
}
div.output.error {
  color: #C00;
}
div.output pre.pre-input {
  margin-bottom: 1em;
}
div.output div.wrap {
  text-wrap: wrap;
}

body::-webkit-scrollbar, div.output pre::-webkit-scrollbar {
    display: none;
}


body, div.output {
  -ms-overflow-style: none; 
  scrollbar-width: none;
}

.viceRadek-kontejner {
  position: fixed;
  height: 100%;
  width: 100%;
}

.floatMenu {
  position: fixed;
  bottom: 0;
  left: 0;
  background-color: #000;
}
.floatMenu .floatItem {
  display: inline-block;
  padding: 15px 25px;
}
.floatMenu .floatItem.object {
  color: #444;
  border: 1px solid #444;
}
.floatMenu .floatItem.string {
  color: #0C0;
  border: 1px solid #0C0;      
}

div.canvas {
  z-index: 1;
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: #FFF;
  color: #000;
  overflow: auto;

}

div.canvas div.errorMessage {
  color: #F00;
  font-weight: bold;
}

div.canvas div.close-canvas {
  cursor: pointer;
  position: fixed;
  right: 0;
  top: 0;
  padding: 10px;
  background-color: #CCC;
}

</style>