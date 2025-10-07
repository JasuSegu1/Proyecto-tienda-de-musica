document.querySelectorAll('input[maxlength]').forEach(input =>{
  let max = Number(input.getAttribute('maxlength'));
  input.oninput = () =>{
    if (input.value.length > max){
      input.value = input.value.slice(0, max);
    }
  };
});

document.querySelectorAll('.qty-input').forEach(input =>{
  let form = input.closest('form');
  let btn = form.querySelector('.update-btn');
  let og = input.value;

  input.addEventListener('input', () =>{
    btn.disabled = !input.value || input.value === og;
  });

  input.addEventListener('keydown', e =>{
    if(e.key === 'Enter' && !btn.disabled){
      e.preventDefault();
      btn.click();
    }
  });
});

let openCart = document.getElementById('open-cart');
let closeCart = document.getElementById('close-cart');
let cart = document.querySelector('.cart');

openCart.onclick = (e) =>{
  e.stopPropagation();
  cart.classList.add('active');
}

closeCart.onclick = (e) =>{
  e.stopPropagation();
  cart.classList.remove('active');
}

document.onclick = (e) =>{
  if(cart.classList.contains('active') && !cart.contains(e.target)){
    cart.classList.remove('active');
  }
}