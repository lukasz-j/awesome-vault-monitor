var React = require('react');
var ReactDOM = require('react-dom');
var App = require('./App.js').App;

let domContainer = document.querySelector('#root');
ReactDOM.render(<App />, domContainer);