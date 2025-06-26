const nextStep = async () => {
        const currentIndex = steps.indexOf(currentStep);
        if (currentIndex < steps.length - 1) {
            // Validación específica para el Step 2 (direcciones)
            if (currentStep === "step2") {
                // Verificar que exista al menos una dirección
                if (!formData.address || formData.address.length === 0) {
                    toast.error("Debe agregar al menos una dirección", {cancel:{
                        label: 'Cerrar',
                        onClick: () => {
                          console.log('Cerrar')
                        },
                        
                      }});
                    return;
                }

                // Validar que todos los campos requeridos estén completos
                const lastAddress = formData.address[formData.address.length - 1];
                const isAddressComplete = 
                    lastAddress.direccion?.trim() !== "" && 
                    lastAddress.depatamentoid !== null && 
                    lastAddress.provinciaid !== null && 
                    lastAddress.distritoid !== null 

                if (!isAddressComplete) {
                    toast.error("Debe completar todos los campos obligatorios de la dirección", {
                        cancel:{
                            label: 'Cerrar',
                            onClick: () => {
                              console.log('Cerrar')
                            },
                            
                          }
                    });
                    return;
                }

                // Validar con react-hook-form
                const isValid = await trigger(
                    formData.address.map((_, index) => [
                        `address.${index}.direccion`,
                        `address.${index}.depatamentoid`,
                        `address.${index}.provinciaid`,
                        `address.${index}.distritoid`,
                        `address.${index}.condicion`,
                        `address.${index}.tipodomicilio`,
                        `address.${index}.principal`
                    ]).flat() as (keyof CreditCreate)[]
                );

                if (!isValid) {
                    toast.error("Por favor, complete todos los campos requeridos", {
                        cancel:{
                            label: 'Cerrar',
                            onClick: () => {
                              console.log('Cerrar')
                            },
                            
                        }
                    });
                    return;
                }
                setCurrentStep(steps[currentIndex + 1]);
                return;
            }
            if (currentStep === "step3") {
                if (formData.montoprestamo === 0) {
                    toast.error("El monto de préstamo no puede ser 0", {
                        cancel:{
                            label: 'Cerrar',
                            onClick: () => {
                              console.log('Cerrar')
                            },
                            
                          }
                    });
                    return;
                }
            }
            // Lógica existente para otros pasos
            if (currentStep === "step1" && formData.dni) {
                try {
                    // Crear un evento sintético para pasar a handleSearch
                    const syntheticEvent = {
                        preventDefault: () => {}
                    } as React.FormEvent<HTMLFormElement>;
                    
                    // Ejecutar la búsqueda
                    await handleSearch(syntheticEvent);
                    
                    // Continuar con la validación normal
                    const stepFields = getStepFields(currentStep);
                    const isValid = await trigger(stepFields);
                    if (isValid) {
                        setCurrentStep(steps[currentIndex + 1]);
                    }
                } catch (error) {
                    console.error("Error al buscar usuario:", error);
                    toast.error("Error al buscar usuario antes de continuar");
                }
            } else {
                const stepFields = getStepFields(currentStep);
                const isValid = await trigger(stepFields);
                if (isValid) {
                    setCurrentStep(steps[currentIndex + 1]);
                }
            }
        }
    };