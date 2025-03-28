function submitForm(event){
    event.preventDefault();
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const message = document.getElementById('message').value;

    const responseDiv =document.getElementById('response');
    responseDiv.innerHTML = <strong>Name:   ${name}</strong>;
    <strong>Email: ${email} </strong>;
    <strong>Message: ${message}</strong>;
    
}