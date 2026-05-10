<h1>Inventory Forecasting Module</h1>

<label for="forecastPeriod">Select Forecast Period:</label>
<select id="forecastPeriod">
    <option value="1">Last 1 Day</option>
    <option value="7">Last 7 Days</option>
    <option value="30">Last 30 Days</option>
    <option value="90">Last 90 Days</option>
    <option value="365">Last 1 Year</option>
</select>

<button id="forecastButton">Generate Forecast</button>

<h2>Forecast Results:</h2>
<div id="forecastOutput"></div>

<script src="../API/forecasting.js"></script> <!-- Include the forecasting module -->
