import Chart from 'chart.js/auto'

async function initDashboardCharts(){
  const canvases = document.querySelectorAll('canvas[data-chart]')
  if(!canvases.length) return
  try{
    const url = new URL(window.location.href)
    url.searchParams.set('accept','json')
    const res = await fetch(url.toString(), {headers:{'Accept':'application/json'}})
    const data = res.ok ? await res.json() : null
    canvases.forEach(cv => {
      const id = cv.getAttribute('data-chart')
      let labels=[], values=[]
      if(id === 'sales_day'){
        labels = Array.from({length:7}, (_,i)=>`D-${6-i}`)
        values = (data?.days ?? Array(7).fill(0))
      } else if(id === 'sales_hour'){
        labels = Array.from({length:24}, (_,i)=>`${i}h`)
        values = (data?.hours ?? Array(24).fill(0))
      }
      new Chart(cv, {
        type: 'line',
        data: { labels, datasets: [{ label: '', data: values, borderColor: '#2563EB', backgroundColor: 'rgba(37,99,235,0.2)', tension: .3 }] },
        options: { responsive: true, plugins: { legend: {display:false} } }
      })
    })
  }catch(e){ /* noop */ }
}

document.addEventListener('DOMContentLoaded', initDashboardCharts)

